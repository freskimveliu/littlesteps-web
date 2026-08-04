<?php

declare(strict_types=1);

namespace App\Actions\Chapters;

use App\Models\Child;
use App\Models\ChildChapter;
use App\Models\ChildMilestone;
use App\Models\User;
use App\Support\Reordering;
use Illuminate\Validation\ValidationException;

class ReorderMilestones
{
    /** @param  array<int, int>  $ids  the running order the parent submitted */
    public function handle(ChildChapter $chapter, Child $child, array $ids, User $editor): void
    {
        abort_if($chapter->isCompleted(), 403, 'This chapter is finished — its map cannot change.');
        abort_if(
            ! $chapter->isUnlockedFor($child),
            403,
            'This chapter has not opened yet — there is nothing in it to arrange.',
        );

        $order = Reordering::of($ids, $chapter->milestones()->orderBy('sort_order')->get());

        if (! $order->matches()) {
            throw ValidationException::withMessages([
                'milestones' => 'That list does not match the milestones in this chapter.',
            ]);
        }

        if (! $order->keepsPinned(fn (ChildMilestone $milestone) => $milestone->isPinned($child))) {
            throw ValidationException::withMessages([
                'milestones' => 'A milestone that names a date keeps its place in the order.',
            ]);
        }

        $order->applyTo(fn () => $chapter->milestones(), $editor->id);
    }
}
