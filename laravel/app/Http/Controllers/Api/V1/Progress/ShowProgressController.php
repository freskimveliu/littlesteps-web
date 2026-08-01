<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Progress;

use App\Http\Controllers\Controller;
use App\Http\Resources\AchievementResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\Achievement;
use App\Support\Limits;
use App\Support\Progress\LevelLadder;
use App\Support\Progress\Metrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Everything the Awards screen needs: level, badges with their progress, and
 * what is still allowed today. All of it computed here — the app displays.
 */
class ShowProgressController extends Controller
{
    public function __invoke(Request $request, Child $child, Metrics $metrics, Limits $limits): JsonResponse
    {
        $this->authorize('view', $child);

        $counts = $metrics->for($child);
        $held = $child->achievements()->pluck('unlocked_at', 'achievement_id');

        $badges = Achievement::query()
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Achievement $badge) => new AchievementResource(
                $badge,
                $counts[$badge->metric->value] ?? 0,
                $held->has($badge->id),
            ));

        $user = $request->user();

        return ApiResponse::success([
            'xp' => $child->xp,
            'level' => LevelLadder::for($child->xp),
            'levelCount' => LevelLadder::total(),
            'metrics' => $counts,
            'badges' => $badges,
            'badgesUnlocked' => $held->count(),
            'badgesTotal' => $badges->count(),
            'limits' => [
                'freeEntriesLeft' => $limits->freeEntriesLeft($child, $user),
                'milestoneEntriesLeft' => $limits->milestoneEntriesLeft($child, $user),
            ],
        ]);
    }
}
