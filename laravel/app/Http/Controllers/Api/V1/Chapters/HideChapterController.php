<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chapters;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildChapterResource;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildChapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Hiding is how a parent says "not for us" without losing anything: the rows
 * stay, the XP stops counting, and any memory already written keeps its place
 * in the timeline.
 */
class HideChapterController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildChapter $chapter): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($chapter->child_id === $child->id, 404);

        $hidden = $request->boolean('hidden', true);

        $chapter->update([
            'is_hidden' => $hidden,
            'updated_by_user_id' => $request->user()->id,
        ]);

        $chapter->milestones()->update(['is_hidden' => $hidden]);

        return ApiResponse::success(
            new ChildChapterResource($chapter->fresh()->load([
                'child',
                'milestones' => fn ($q) => $q->orderBy('sort_order')
                    ->with(['category', 'properties', 'entry.properties', 'entry.media', 'child']),
            ])),
            $hidden ? 'Chapter hidden.' : 'Chapter restored.',
        );
    }
}
