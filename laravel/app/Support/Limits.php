<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AppSettingKey;
use App\Models\Child;
use App\Models\ChildChapter;
use App\Models\ChildMilestone;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * How much can be recorded in a day.
 *
 * The point is not to be mean — it is that a keepsake filled in one sitting and
 * then abandoned is worth less than one built a day at a time. Counting is done
 * on created_at in the user's own timezone, never on the memory's date, so
 * back-dating an old photo is always allowed.
 *
 * `abilities()` asks the chapter questions once per chapter, so when the
 * milestones are already in memory they are counted there rather than requeried.
 */
class Limits
{
    public function __construct(private readonly Settings $settings) {}

    public function freeEntriesLeft(Child $child, User $user): int
    {
        $used = $child->entries()
            ->free()
            ->whereBetween('created_at', $this->today($user))
            ->count();

        return max(0, $this->settings->number(AppSettingKey::DailyFreeEntries) - $used);
    }

    public function milestoneEntriesLeft(Child $child, User $user): int
    {
        $used = $child->entries()
            ->whereNotNull('child_milestone_id')
            ->whereBetween('created_at', $this->today($user))
            ->count();

        return max(0, $this->settings->number(AppSettingKey::DailyMilestoneEntries) - $used);
    }

    public function canAddCustomMilestone(ChildChapter $chapter): bool
    {
        $custom = $chapter->relationLoaded('milestones')
            ? $chapter->milestones->where('is_editable', true)->count()
            : $chapter->milestones()->where('is_editable', true)->count();

        return $custom < $this->settings->number(AppSettingKey::MaxCustomMilestonesPerChapter);
    }

    /**
     * A chapter can be finished once every milestone in it has a memory — and only
     * if there are enough of them, which is what stops a parent deleting a chapter
     * down to two milestones, filling both, and collecting the gift.
     */
    public function canCompleteChapter(ChildChapter $chapter): bool
    {
        if ($chapter->completed_at !== null) {
            return false;
        }

        [$total, $recorded] = $this->tally($chapter);

        if ($total < $this->settings->number(AppSettingKey::MinMilestonesToCompleteChapter)) {
            return false;
        }

        return $recorded === $total;
    }

    public function freeEntryXp(): int
    {
        return $this->settings->number(AppSettingKey::FreeEntryXp);
    }

    public function maxMediaPerEntry(): int
    {
        return $this->settings->number(AppSettingKey::MaxMediaPerEntry);
    }

    /**
     * How many milestones this chapter holds, and how many are recorded.
     *
     * @return array{0: int, 1: int}
     */
    private function tally(ChildChapter $chapter): array
    {
        /** @var Collection<int, ChildMilestone> $milestones */
        $milestones = $chapter->relationLoaded('milestones') ? $chapter->milestones : collect();

        // Without the entries alongside them, asking each milestone whether it is
        // recorded is a query apiece — worse than the two counts it replaces.
        $inMemory = $chapter->relationLoaded('milestones')
            && $milestones->every(fn (ChildMilestone $milestone) => $milestone->relationLoaded('entry'));

        if (! $inMemory) {
            return [
                $chapter->milestones()->count(),
                $chapter->milestones()->whereHas('entry')->count(),
            ];
        }

        return [
            $milestones->count(),
            $milestones->filter(fn (ChildMilestone $milestone) => $milestone->isRecorded())->count(),
        ];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function today(User $user): array
    {
        $zone = $user->timezone ?: 'UTC';
        $start = now($zone)->startOfDay();

        return [
            CarbonImmutable::parse($start)->utc(),
            CarbonImmutable::parse($start->copy()->endOfDay())->utc(),
        ];
    }
}
