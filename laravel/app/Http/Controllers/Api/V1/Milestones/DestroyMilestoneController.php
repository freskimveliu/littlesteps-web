<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Milestones;

use App\Actions\Milestones\DeleteMilestone;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildMilestone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DestroyMilestoneController extends Controller
{
    public function __invoke(
        Request $request,
        Child $child,
        ChildMilestone $milestone,
        DeleteMilestone $delete,
    ): JsonResponse {
        $this->authorize('contribute', $child);
        abort_unless($milestone->child_id === $child->id, 404);

        $delete->handle($milestone, $request->user());

        return ApiResponse::noContent();
    }
}
