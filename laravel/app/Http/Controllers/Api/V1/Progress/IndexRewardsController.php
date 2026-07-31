<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Progress;

use App\Http\Controllers\Controller;
use App\Http\Resources\RewardResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexRewardsController extends Controller
{
    public function __invoke(Request $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        $rewards = $child->rewards()
            ->with(['childAchievement.achievement', 'media'])
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success(RewardResource::collection($rewards));
    }
}
