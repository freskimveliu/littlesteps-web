<?php

declare(strict_types=1);

namespace App\Actions\Progress;

use App\Models\Child;

/**
 * XP only ever goes up. Deleting a memory must not cost a level the child has
 * already been shown, so this is an increment rather than a recalculation.
 */
class AwardXp
{
    public function handle(Child $child, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $child->increment('xp', $amount);
    }
}
