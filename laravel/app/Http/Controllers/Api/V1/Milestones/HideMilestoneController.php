<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Milestones;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildMilestoneResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildMilestone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HideMilestoneController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildMilestone $milestone): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($milestone->child_id === $child->id, 404);
        abort_if($milestone->isSealed(), 403, 'This chapter is finished — its map cannot change.');

        $hidden = $request->boolean('hidden', true);

        $milestone->update([
            'is_hidden' => $hidden,
            'updated_by_user_id' => $request->user()->id,
        ]);

        return ApiResponse::success(
            new ChildMilestoneResource($milestone->fresh()->load(['category', 'properties', 'entry', 'child', 'chapter'])),
            $hidden ? 'Milestone hidden.' : 'Milestone restored.',
        );
    }
}
