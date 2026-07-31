<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The numbers that pace the app, tunable from the admin without a release.
 *
 * These are global — a preference belonging to somebody is a SettingKey.
 */
enum AppSettingKey: string
{
    case DailyFreeEntries = 'daily_free_entries';
    case FreeEntryXp = 'free_entry_xp';
    case DailyStepEntries = 'daily_step_entries';
    case MaxCustomStepsPerMilestone = 'max_custom_steps_per_milestone';
    case MinStepsToCompleteMilestone = 'min_steps_to_complete_milestone';

    public function default(): int
    {
        return match ($this) {
            self::DailyFreeEntries => 1,
            self::FreeEntryXp => 10,
            self::DailyStepEntries => 5,
            self::MaxCustomStepsPerMilestone => 10,
            self::MinStepsToCompleteMilestone => 10,
        };
    }
}
