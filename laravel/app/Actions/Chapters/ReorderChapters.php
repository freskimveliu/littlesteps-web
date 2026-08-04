<?php

declare(strict_types=1);

namespace App\Actions\Chapters;

use App\Models\Child;
use App\Models\ChildChapter;
use App\Models\User;
use App\Support\Reordering;
use Illuminate\Validation\ValidationException;

class ReorderChapters
{
    /** @param  array<int, int>  $ids  the running order the parent submitted */
    public function handle(Child $child, array $ids, User $editor): void
    {
        $order = Reordering::of($ids, $child->chapters()->orderBy('sort_order')->get());

        if (! $order->matches()) {
            throw ValidationException::withMessages([
                'chapters' => 'That list does not match this child’s chapters.',
            ]);
        }

        if (! $order->keepsPinned(fn (ChildChapter $chapter) => $chapter->isPinned($child))) {
            throw ValidationException::withMessages([
                'chapters' => 'A guided chapter, and one the child has not reached, each keep their place.',
            ]);
        }

        $order->applyTo(fn () => $child->chapters(), $editor->id);
    }
}
