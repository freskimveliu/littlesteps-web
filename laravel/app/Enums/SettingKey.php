<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Preferences belonging to a user (and later a child) — one row each in settings.
 */
enum SettingKey: string
{
    case MilestoneReminders = 'milestone_reminders';
    case DailyQuests = 'daily_quests';
    case StreakAlerts = 'streak_alerts';

    /**
     * The column spells these in snake_case and the payload hands them back in
     * camelCase, so a patch may arrive either way. Both are the same switch.
     */
    public static function match(string $key): ?self
    {
        return self::tryFrom($key) ?? self::tryFrom(Str::snake($key));
    }

    public function default(): bool
    {
        return true;
    }
}
