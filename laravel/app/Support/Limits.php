<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AppSettingKey;
use App\Models\AppSetting;
use App\Models\Child;
use App\Models\ChildChapter;
use App\Models\User;

/**
 * How much can be recorded in a day.
 *
 * The point is not to be mean — it is that a keepsake filled in one sitting and
 * then abandoned is worth less than one built a day at a time. Counting is done
 * on created_at in the user's own timezone, never on the memory's date, so
 * back-dating an old photo is always allowed.
 */
class Limits
{
    public function freeEntriesLeft(Child $child, User $user): int
    {
        $used = $child->entries()
            ->free()
            ->whereBetween('created_at', $this->today($user))
            ->count();

        return max(0, AppSetting::number(AppSettingKey::DailyFreeEntries) - $used);
    }

    public function milestoneEntriesLeft(Child $child, User $user): int
    {
        $used = $child->entries()
            ->whereNotNull('child_milestone_id')
            ->whereBetween('created_at', $this->today($user))
            ->count();

        return max(0, AppSetting::number(AppSettingKey::DailyMilestoneEntries) - $used);
    }

    public function canAddCustomMilestone(ChildChapter $chapter): bool
    {
        $custom = $chapter->milestones()->where('is_editable', true)->count();

        return $custom < AppSetting::number(AppSettingKey::MaxCustomMilestonesPerChapter);
    }

    /**
     * A chapter can be finished once every visible milestone in it has a memory —
     * and only if there are enough of them, which is what stops a parent hiding
     * a chapter down to two milestones, filling both, and collecting the gift.
     */
    public function canCompleteChapter(ChildChapter $chapter): bool
    {
        if ($chapter->completed_at !== null) {
            return false;
        }

        $visible = $chapter->milestones()->visible()->count();

        if ($visible < AppSetting::number(AppSettingKey::MinMilestonesToCompleteChapter)) {
            return false;
        }

        $recorded = $chapter->milestones()->visible()->whereHas('entry')->count();

        return $recorded === $visible;
    }

    public function freeEntryXp(): int
    {
        return AppSetting::number(AppSettingKey::FreeEntryXp);
    }

    public function maxMediaPerEntry(): int
    {
        return AppSetting::number(AppSettingKey::MaxMediaPerEntry);
    }

    /** @return array{0: \Carbon\CarbonImmutable, 1: \Carbon\CarbonImmutable} */
    private function today(User $user): array
    {
        $zone = $user->timezone ?: 'UTC';
        $start = now($zone)->startOfDay();

        return [
            \Carbon\CarbonImmutable::parse($start)->utc(),
            \Carbon\CarbonImmutable::parse($start->copy()->endOfDay())->utc(),
        ];
    }
}
