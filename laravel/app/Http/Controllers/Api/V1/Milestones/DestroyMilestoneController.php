<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Milestones;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildMilestone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A milestone can only be deleted while it is still empty — guided or not.
 *
 * Once a memory exists the XP has been taken and a daily slot has been spent;
 * allowing a delete then would hand both back. A parent who wants a recorded
 * milestone off the map hides it instead.
 */
class DestroyMilestoneController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildMilestone $milestone): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($milestone->child_id === $child->id, 404);

        abort_if($milestone->isSealed(), 403, 'This chapter is finished — its map cannot change.');

        abort_unless(
            $milestone->isDeletable(),
            403,
            'This milestone already holds a memory. Hide it instead.',
        );

        $milestone->delete();

        return ApiResponse::noContent();
    }
}
