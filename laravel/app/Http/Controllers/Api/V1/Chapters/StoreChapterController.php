<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chapters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreChapterRequest;
use App\Http\Resources\ChildChapterResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use Illuminate\Http\JsonResponse;

/**
 * A chapter the parent writes themselves — their own part of the story
 * alongside the guided one.
 *
 * It carries no xp: the ladder is paced by the catalogue, and a chapter anyone
 * can create and finish would be a way to mint levels rather than earn them.
 */
class StoreChapterController extends Controller
{
    public function __invoke(StoreChapterRequest $request, Child $child): JsonResponse
    {
        $this->authorize('contribute', $child);

        $chapter = $child->chapters()->create([
            ...$request->validated(),
            'xp' => 0,
            'sort_order' => ($child->chapters()->max('sort_order') ?? 0) + 10,
            'is_editable' => true,
            'created_by_user_id' => $request->user()->id,
        ]);

        return ApiResponse::success(
            new ChildChapterResource($chapter->load(['child', 'milestones'])),
            'Chapter added.',
            201,
        );
    }
}
