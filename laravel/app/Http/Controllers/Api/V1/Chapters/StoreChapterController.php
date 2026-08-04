<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chapters;

use App\Actions\Chapters\CreateChapter;
use App\Data\ChapterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreChapterRequest;
use App\Http\Resources\ChildChapterResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;

class StoreChapterController extends Controller
{
    public function __invoke(StoreChapterRequest $request, Child $child, CreateChapter $create): JsonResponse
    {
        $this->authorize('contribute', $child);

        $chapter = $create->handle($child, $request->user(), ChapterData::fromRequest($request));

        return ApiResponse::success(
            new ChildChapterResource($chapter->load(['child', 'milestones'])),
            'Chapter added.',
            201,
        );
    }
}
