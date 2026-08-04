<?php

declare(strict_types=1);

namespace App\Actions\Chapters;

use App\Models\Child;
use App\Models\ChildChapter;
use Illuminate\Support\Facades\DB;

/**
 * A chapter goes while it is still unfinished, and never one holding a memory —
 * deleting cascades to its milestones and their entries, which is the one thing
 * this app must never do quietly. Naming somewhere to move them to is what makes
 * it safe: the milestones are carried over first and the chapter leaves empty.
 */
class DeleteChapter
{
    public function handle(ChildChapter $chapter, Child $child, ?int $moveTo = null): void
    {
        abort_unless(
            $chapter->isDeletable(),
            403,
            $chapter->isCompleted()
                ? 'This chapter is finished. Its gift has already been given.'
                : 'A child needs at least one chapter.',
        );

        $target = $moveTo !== null
            ? $child->chapters()->where('id', '!=', $chapter->id)->findOrFail($moveTo)
            : null;

        // The seal the single-milestone move already honours: a finished chapter
        // counted its milestones when its gift was given, so a whole chapter
        // emptied into it would leave it complete with empty nodes.
        abort_if(
            $target?->isCompleted() ?? false,
            403,
            'That chapter is finished and cannot take new milestones.',
        );

        abort_if(
            $target !== null && ! $target->isUnlockedFor($child),
            403,
            'That chapter has not opened yet and cannot take new milestones.',
        );

        abort_if(
            $target !== null && $chapter->milestones()->where('is_date_editable', false)->exists(),
            403,
            'This chapter holds a milestone that names a date, which cannot be carried anywhere else. Delete it first.',
        );

        abort_if(
            ! $target && $chapter->milestones()->whereHas('entry')->exists(),
            403,
            'This chapter holds a memory. Move its milestones somewhere else first.',
        );

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
    }
}
