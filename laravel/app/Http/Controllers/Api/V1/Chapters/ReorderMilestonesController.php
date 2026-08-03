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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The whole order in one call rather than a nudge per milestone: two parents
 * each moving something a place would otherwise land on the same sort_order.
 */
class ReorderMilestonesController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildChapter $chapter): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($chapter->child_id === $child->id, 404);
        abort_if($chapter->isCompleted(), 403, 'This chapter is finished — its map cannot change.');

        $validated = $request->validate([
            'milestones' => ['required', 'array', 'min:1'],
            'milestones.*' => ['integer'],
        ]);

        $ordered = collect($validated['milestones']);
        $own = $chapter->milestones()->pluck('id');

        if ($ordered->duplicates()->isNotEmpty() || $ordered->diff($own)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'milestones' => 'That list does not match the milestones in this chapter.',
            ]);
        }

        DB::transaction(function () use ($ordered, $chapter, $request) {
            foreach ($ordered as $i => $id) {
                $chapter->milestones()->whereKey($id)->update([
                    'sort_order' => ($i + 1) * 10,
                    'updated_by_user_id' => $request->user()->id,
                ]);
            }
        });

        return ApiResponse::success(
            new ChildChapterResource($chapter->fresh()->load([
                'child',
                'milestones' => fn ($q) => $q->orderBy('sort_order')
                    ->with(['category', 'properties', 'entry.properties', 'entry.media', 'child']),
            ])),
            'Reordered.',
        );
    }
}
