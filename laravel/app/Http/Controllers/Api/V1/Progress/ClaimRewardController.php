<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Progress;

use App\Enums\RewardStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\RewardResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildReward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Claiming is what starts a generation, never earning the badge — so a lapsed
 * account never costs one, and a failure can be retried without a duplicate.
 *
 * TODO: dispatch the generation job once the story/image/book prompts exist.
 * Until then the row simply moves to generating and waits.
 */
class ClaimRewardController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildReward $reward): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($reward->child_id === $child->id, 404);

        abort_unless($reward->status->isClaimable(), 409, 'This gift is already on its way.');

        $reward->update([
            'status' => RewardStatus::Generating,
            'claimed_at' => $reward->claimed_at ?? now(),
        ]);

        return ApiResponse::success(
            new RewardResource($reward->fresh()->load('childAchievement.achievement')),
            'We are making it now — we will let you know when it is ready.',
        );
    }
}
