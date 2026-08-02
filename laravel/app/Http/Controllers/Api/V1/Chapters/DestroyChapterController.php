<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chapters;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Child;
use App\Models\ChildChapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * A chapter goes while it is still unfinished, and never one holding a memory —
 * deleting cascades to its milestones and their entries, which is the one thing
 * this app must never do quietly. Pass move_milestones_to to keep them.
 */
class DestroyChapterController extends Controller
{
    public function __invoke(Request $request, Child $child, ChildChapter $chapter): JsonResponse
    {
        $this->authorize('contribute', $child);
        abort_unless($chapter->child_id === $child->id, 404);

        abort_unless(
            $chapter->isDeletable(),
            403,
            $chapter->isCompleted()
                ? 'This chapter is finished. Its gift has already been given.'
                : 'A child needs at least one chapter.',
        );

        $validated = $request->validate([
            'move_milestones_to' => ['nullable', 'integer'],
        ]);

        $target = isset($validated['move_milestones_to'])
            ? $child->chapters()->where('id', '!=', $chapter->id)->findOrFail($validated['move_milestones_to'])
            : null;

        if (! $target && $chapter->milestones()->whereHas('entry')->exists()) {
            abort(403, 'This chapter holds a memory. Move its milestones somewhere else first.');
        }

        DB::transaction(function () use ($chapter, $target) {
            if ($target) {
                $next = ($target->milestones()->max('sort_order') ?? 0) + 10;

                foreach ($chapter->milestones()->orderBy('sort_order')->get() as $milestone) {
                    $milestone->update(['child_chapter_id' => $target->id, 'sort_order' => $next]);
                    $next += 10;
                }
            }

            $chapter->delete();
        });

        return ApiResponse::noContent();
    }
}
