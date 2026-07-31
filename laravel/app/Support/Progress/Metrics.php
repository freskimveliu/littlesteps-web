<?php

declare(strict_types=1);

namespace App\Support\Progress;

use App\Enums\AchievementMetric;
use App\Models\Child;
use App\Models\ChildEntry;
use Illuminate\Support\Facades\DB;

/**
 * The numbers every badge rule reads.
 *
 * Each one is gated by the calendar rather than by typing speed: the catalogue
 * holds a finite number of steps, so a raw entry count would let a determined
 * parent clear the whole ladder in a fortnight.
 */
class Metrics
{
    /** @return array<string, int> */
    public function for(Child $child): array
    {
        return [
            AchievementMetric::Days->value => $this->days($child),
            AchievementMetric::Months->value => $this->months($child),
            AchievementMetric::Streak->value => $this->streak($child),
            AchievementMetric::OnTimeSteps->value => $this->onTimeSteps($child),
            AchievementMetric::Chapters->value => $this->chapters($child),
            AchievementMetric::Photos->value => $this->photos($child),
            AchievementMetric::Categories->value => $this->categories($child),
        ];
    }

    public function days(Child $child): int
    {
        return $child->entries()->distinct()->count('date');
    }

    public function months(Child $child): int
    {
        return $child->entries()
            ->select(DB::raw("DISTINCT DATE_FORMAT(date, '%Y-%m') as ym"))
            ->get()
            ->count();
    }

    /** Consecutive days ending at the most recent memory. */
    public function streak(Child $child): int
    {
        $dates = $child->entries()
            ->orderByDesc('date')
            ->pluck('date')
            ->map(fn ($date) => $date->toDateString())
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            return 0;
        }

        $streak = 1;
        $cursor = \Carbon\CarbonImmutable::parse($dates[0]);

        for ($i = 1; $i < $dates->count(); $i++) {
            $next = \Carbon\CarbonImmutable::parse($dates[$i]);

            if ($cursor->subDay()->isSameDay($next)) {
                $streak++;
                $cursor = $next;

                continue;
            }

            break;
        }

        return $streak;
    }

    /**
     * A step caught while the child was actually that age — recorded inside the
     * quarter that opens at the step's months_from. Backfilling the first smile
     * at age four is still a memory, just not this badge.
     */
    public function onTimeSteps(Child $child): int
    {
        return ChildEntry::query()
            ->where('child_entries.child_id', $child->id)
            ->join('child_steps', 'child_steps.id', '=', 'child_entries.child_step_id')
            ->whereNotNull('child_steps.months_from')
            ->whereRaw(
                'child_entries.date >= DATE_ADD(?, INTERVAL child_steps.months_from MONTH)',
                [$child->birthday->toDateString()]
            )
            ->whereRaw(
                'child_entries.date < DATE_ADD(?, INTERVAL child_steps.months_from + 3 MONTH)',
                [$child->birthday->toDateString()]
            )
            ->count();
    }

    public function chapters(Child $child): int
    {
        return $child->milestones()->whereNotNull('completed_at')->count();
    }

    public function photos(Child $child): int
    {
        return DB::table('media')
            ->where('model_type', ChildEntry::class)
            ->where('collection_name', ChildEntry::PHOTOS)
            ->whereIn('model_id', $child->entries()->select('id'))
            ->count();
    }

    public function categories(Child $child): int
    {
        return ChildEntry::query()
            ->where('child_entries.child_id', $child->id)
            ->join('child_steps', 'child_steps.id', '=', 'child_entries.child_step_id')
            ->whereNotNull('child_steps.category_id')
            ->distinct()
            ->count('child_steps.category_id');
    }
}
