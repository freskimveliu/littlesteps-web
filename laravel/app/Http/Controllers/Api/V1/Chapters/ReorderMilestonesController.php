<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chapters;

use App\Actions\Chapters\ReorderMilestones;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReorderMilestonesRequest;
use App\Http\Resources\ChildChapterResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildChapter;
use Illuminate\Http\JsonResponse;

class ReorderMilestonesController extends Controller
{
    public function __invoke(
        ReorderMilestonesRequest $request,
        Child $child,
        ChildChapter $chapter,
        ReorderMilestones $reorder,
    ): JsonResponse {
        $this->authorize('contribute', $child);
        abort_unless($chapter->child_id === $child->id, 404);

        $reorder->handle($chapter, $child, $request->ids(), $request->user());

        return ApiResponse::success(
            new ChildChapterResource($chapter->fresh()->load(ChildChapter::map())),
            'Reordered.',
        );
    }
}
