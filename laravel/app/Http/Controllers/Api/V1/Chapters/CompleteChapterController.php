<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chapters;

use App\Actions\Chapters\CompleteChapter;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChildChapterResource;
use App\Http\Resources\EarnedTrophyResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildChapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompleteChapterController extends Controller
{
    public function __invoke(
        Request $request,
        Child $child,
        ChildChapter $chapter,
        CompleteChapter $complete,
    ): JsonResponse {
        $this->authorize('contribute', $child);
        abort_unless($chapter->child_id === $child->id, 404);

        // Said here rather than left to the action, which can only report that a
        // finished chapter is not completable — and does so by asking for the
        // memories it already has.
        abort_if($chapter->isCompleted(), 403, 'This chapter is already finished.');

        $result = $complete->handle($chapter, $request->user());

        return ApiResponse::success([
            'chapter' => new ChildChapterResource($result['chapter']->load(ChildChapter::map())),
            'xpEarned' => $result['xp'],
            'unlocked' => $result['unlocked']->map(
                fn ($held) => new EarnedTrophyResource($held)
            ),
        ], 'Chapter complete.');
    }
}
