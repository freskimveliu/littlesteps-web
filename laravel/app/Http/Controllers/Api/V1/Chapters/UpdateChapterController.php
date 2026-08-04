<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chapters;

use App\Actions\Chapters\UpdateChapter;
use App\Data\ChapterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateChapterRequest;
use App\Http\Resources\ChildChapterResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildChapter;
use Illuminate\Http\JsonResponse;

class UpdateChapterController extends Controller
{
    public function __invoke(
        UpdateChapterRequest $request,
        Child $child,
        ChildChapter $chapter,
        UpdateChapter $update,
    ): JsonResponse {
        $this->authorize('contribute', $child);
        abort_unless($chapter->child_id === $child->id, 404);
        abort_if($chapter->isCompleted(), 403, 'This chapter is finished — its map cannot change.');

        $chapter = $update->handle($chapter, $request->user(), ChapterData::fromRequest($request));

        return ApiResponse::success(
            new ChildChapterResource($chapter->load(ChildChapter::map())),
            'Saved.',
        );
    }
}
