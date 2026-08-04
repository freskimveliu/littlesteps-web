<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Progress;

use App\Actions\Progress\ClaimReward;
use App\Http\Controllers\Controller;
use App\Http\Resources\RewardResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildReward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaimRewardController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildReward $reward, ClaimReward $claim): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($reward->child_id === $child->id, 404);

        $reward = $claim->handle($reward);

        return ApiResponse::success(
            new RewardResource($reward->load('childTrophy')),
            'We are making it now — we will let you know when it is ready.',
        );
    }
}
