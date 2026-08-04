<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Entries;

use App\Actions\Entries\UpdateEntry;
use App\Data\EntryChangeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateEntryRequest;
use App\Http\Resources\ChildEntryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildEntry;
use Illuminate\Http\JsonResponse;

class UpdateEntryController extends Controller
{
    public function __invoke(
        UpdateEntryRequest $request,
        Child $child,
        ChildEntry $entry,
        UpdateEntry $update,
    ): JsonResponse {
        $this->authorize('contribute', $child);
        abort_unless($entry->child_id === $child->id, 404);

        $entry = $update->handle($entry, $child, $request->user(), EntryChangeData::fromRequest($request));

        return ApiResponse::success(new ChildEntryResource($entry), 'Saved.');
    }
}
