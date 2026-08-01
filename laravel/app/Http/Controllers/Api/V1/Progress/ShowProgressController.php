<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Progress;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrophyResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\Trophy;
use App\Support\Limits;
use App\Support\Progress\LevelLadder;
use App\Support\Progress\Metrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Everything the Awards screen needs: level, trophies with their progress, and
 * what is still allowed today. All of it computed here — the app displays.
 */
class ShowProgressController extends Controller
{
    public function __invoke(Request $request, Child $child, Metrics $metrics, Limits $limits): JsonResponse
    {
        $this->authorize('view', $child);

        $counts = $metrics->for($child);
        $held = $child->trophies()->pluck('unlocked_at', 'trophy_id');

        $trophies = Trophy::query()
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Trophy $trophy) => new TrophyResource(
                $trophy,
                $counts[$trophy->metric->value] ?? 0,
                $held->has($trophy->id),
            ));

        $user = $request->user();

        return ApiResponse::success([
            'xp' => $child->xp,
            'level' => LevelLadder::for($child->xp),
            'levelCount' => LevelLadder::total(),
            'metrics' => $counts,
            'trophies' => $trophies,
            'trophiesUnlocked' => $held->count(),
            'trophiesTotal' => $trophies->count(),
            'limits' => [
                'freeEntriesLeft' => $limits->freeEntriesLeft($child, $user),
                'milestoneEntriesLeft' => $limits->milestoneEntriesLeft($child, $user),
            ],
        ]);
    }
}
