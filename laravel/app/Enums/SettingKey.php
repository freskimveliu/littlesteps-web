<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Preferences belonging to a user (and later a child) — one row each in settings.
 */
enum SettingKey: string
{
    case MilestoneReminders = 'milestone_reminders';
    case DailyQuests = 'daily_quests';
    case StreakAlerts = 'streak_alerts';

    public function default(): bool
    {
        return true;
    }
}
