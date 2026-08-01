<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chapters;

use App\Actions\Chapters\CompleteChapter;
use App\Http\Controllers\Controller;
use App\Http\Resources\AchievementResource;
use App\Http\Resources\ChildChapterResource;
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

        $result = $complete->handle($chapter, $request->user());

        return ApiResponse::success([
            'chapter' => new ChildChapterResource($result['chapter']->load(['child', 'milestones'])),
            'xpEarned' => $result['xp'],
            'unlocked' => $result['unlocked']->map(
                fn ($held) => new AchievementResource($held->achievement, $held->achievement->threshold, true)
            ),
        ], 'Chapter complete.');
    }
}
