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

/**
 * Hiding is how a parent says "not for us" without losing anything: the rows
 * stay, the XP stops counting, and any memory already written keeps its place
 * in the timeline.
 */
class HideMilestoneController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildMilestone $milestone): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($milestone->child_id === $child->id, 404);

        $hidden = $request->boolean('hidden', true);

        $milestone->update([
            'is_hidden' => $hidden,
            'updated_by_user_id' => $request->user()->id,
        ]);

        $milestone->steps()->update(['is_hidden' => $hidden]);

        return ApiResponse::success(
            new ChildMilestoneResource($milestone->fresh()->load(['child', 'steps'])),
            $hidden ? 'Chapter hidden.' : 'Chapter restored.',
        );
    }
}
