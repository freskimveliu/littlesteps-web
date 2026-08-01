<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Entries;

use App\Actions\Entries\RecordEntry;
use App\Data\EntryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEntryRequest;
use App\Http\Resources\ChildEntryResource;
use App\Http\Resources\EarnedTrophyResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Support\Limits;
use App\Support\Progress\LevelLadder;
use Illuminate\Http\JsonResponse;

class StoreEntryController extends Controller
{
    public function __invoke(
        StoreEntryRequest $request,
        Child $child,
        RecordEntry $record,
        Limits $limits,
    ): JsonResponse {
        $this->authorize('contribute', $child);

        $user = $request->user();
        $result = $record->handle($child, $user, EntryData::fromRequest($request));
        $child->refresh();

        return ApiResponse::success([
            'entry' => new ChildEntryResource($result['entry']),
            'xpEarned' => $result['xp'],
            'xp' => $child->xp,
            'level' => LevelLadder::for($child->xp),
            'unlocked' => $result['unlocked']->map(
                fn ($held) => new EarnedTrophyResource($held)
            ),
            'limits' => [
                'freeEntriesLeft' => $limits->freeEntriesLeft($child, $user),
                'milestoneEntriesLeft' => $limits->milestoneEntriesLeft($child, $user),
            ],
        ], 'Memory captured.', 201);
    }
}
