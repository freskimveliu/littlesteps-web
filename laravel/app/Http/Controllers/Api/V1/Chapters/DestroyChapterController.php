<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chapters;

use App\Actions\Chapters\DeleteChapter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DestroyChapterRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildChapter;
use Illuminate\Http\JsonResponse;

class DestroyChapterController extends Controller
{
    public function __invoke(
        DestroyChapterRequest $request,
        Child $child,
        ChildChapter $chapter,
        DeleteChapter $delete,
    ): JsonResponse {
        $this->authorize('contribute', $child);
        abort_unless($chapter->child_id === $child->id, 404);

        $delete->handle($chapter, $child, $request->moveTo());

        return ApiResponse::noContent();
    }
}
