<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Milestones;

use App\Actions\Milestones\CreateMilestone;
use App\Data\MilestoneData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMilestoneRequest;
use App\Http\Resources\ChildMilestoneResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;

class StoreMilestoneController extends Controller
{
    public function __invoke(StoreMilestoneRequest $request, Child $child, CreateMilestone $create): JsonResponse
    {
        $this->authorize('contribute', $child);

        $milestone = $create->handle($child, $request->user(), MilestoneData::fromRequest($request));

        return ApiResponse::success(
            new ChildMilestoneResource($milestone->load(['category', 'properties', 'child', 'chapter'])),
            'Milestone added.',
            201,
        );
    }
}
