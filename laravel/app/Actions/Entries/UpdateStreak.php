<?php

declare(strict_types=1);

namespace App\Actions\Entries;

use App\Models\ChildEntry;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * The parent's streak, not the child's — it follows the person doing the
 * recording. Measured against the day they wrote it in their own timezone,
 * so back-dating an old photo cannot manufacture a streak.
 */
class UpdateStreak
{
    public function handle(User $user, ChildEntry $entry): void
    {
        $today = CarbonImmutable::parse(now($user->timezone ?: 'UTC'))->startOfDay();
        $last = $user->last_entry_date ? CarbonImmutable::parse($user->last_entry_date)->startOfDay() : null;

        if ($last?->isSameDay($today)) {
            return;
        }

        $current = $last && $last->addDay()->isSameDay($today)
            ? $user->current_streak + 1
            : 1;

        $user->forceFill([
            'current_streak' => $current,
            'longest_streak' => max($current, $user->longest_streak),
            'last_entry_date' => $today->toDateString(),
        ])->save();
    }
}
