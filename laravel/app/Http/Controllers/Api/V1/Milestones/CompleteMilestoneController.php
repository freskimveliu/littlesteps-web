<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Milestones;

use App\Actions\Milestones\CompleteMilestone;
use App\Http\Controllers\Controller;
use App\Http\Resources\AchievementResource;
use App\Http\Resources\ChildMilestoneResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildMilestone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompleteMilestoneController extends Controller
{
    public function __invoke(
        Request $request,
        Child $child,
        ChildMilestone $milestone,
        CompleteMilestone $complete,
    ): JsonResponse {
        $this->authorize('contribute', $child);
        abort_unless($milestone->child_id === $child->id, 404);

        $result = $complete->handle($milestone, $request->user());

        return ApiResponse::success([
            'milestone' => new ChildMilestoneResource($result['milestone']->load(['child', 'steps'])),
            'xpEarned' => $result['xp'],
            'unlocked' => $result['unlocked']->map(
                fn ($held) => new AchievementResource($held->achievement, $held->achievement->threshold, true)
            ),
        ], 'Chapter complete.');
    }
}
