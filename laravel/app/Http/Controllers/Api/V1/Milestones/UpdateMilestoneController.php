<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Milestones;

use App\Actions\Milestones\UpdateMilestone;
use App\Data\MilestoneData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateMilestoneRequest;
use App\Http\Resources\ChildMilestoneResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildMilestone;
use Illuminate\Http\JsonResponse;

class UpdateMilestoneController extends Controller
{
    public function __invoke(
        UpdateMilestoneRequest $request,
        Child $child,
        ChildMilestone $milestone,
        UpdateMilestone $update,
    ): JsonResponse {
        $this->authorize('contribute', $child);
        abort_unless($milestone->child_id === $child->id, 404);
        abort_if($milestone->isSealed(), 403, 'This chapter is finished — its map cannot change.');

        $milestone = $update->handle($milestone, $child, $request->user(), MilestoneData::fromRequest($request));

        return ApiResponse::success(
            new ChildMilestoneResource($milestone->load(['category', 'properties', 'entry', 'child', 'chapter'])),
            'Saved.',
        );
    }
}
