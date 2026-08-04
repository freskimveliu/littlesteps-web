<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chapters;

use App\Actions\Chapters\ReorderChapters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReorderChaptersRequest;
use App\Http\Resources\ChildChapterResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildChapter;
use Illuminate\Http\JsonResponse;

class ReorderChaptersController extends Controller
{
    public function __invoke(ReorderChaptersRequest $request, Child $child, ReorderChapters $reorder): JsonResponse
    {
        $this->authorize('contribute', $child);

        $reorder->handle($child, $request->ids(), $request->user());

        $chapters = $child->chapters()
            ->with(ChildChapter::map())
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success(ChildChapterResource::collection($chapters), 'Reordered.');
    }
}
