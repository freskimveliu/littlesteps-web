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
    case DailyMilestoneEntries = 'daily_milestone_entries';
    case MaxCustomMilestonesPerChapter = 'max_custom_milestones_per_chapter';
    case MinMilestonesToCompleteChapter = 'min_milestones_to_complete_chapter';

    public function default(): int
    {
        return match ($this) {
            self::DailyFreeEntries => 1,
            self::FreeEntryXp => 10,
            self::DailyMilestoneEntries => 5,
            self::MaxCustomMilestonesPerChapter => 10,
            self::MinMilestonesToCompleteChapter => 10,
        };
    }
}
