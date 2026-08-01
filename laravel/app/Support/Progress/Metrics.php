<?php

declare(strict_types=1);

namespace App\Support\Progress;

use App\Enums\TrophyMetric;
use App\Models\Child;
use App\Models\ChildEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The numbers every trophy rule reads.
 *
 * Each one is gated by the calendar rather than by typing speed: the catalogue
 * holds a finite number of milestones, so a raw entry count would let a determined
 * parent clear the whole ladder in a fortnight.
 */
class Metrics
{
    /** @return array<string, int> */
    public function for(Child $child): array
    {
        return [
            TrophyMetric::Days->value => $this->days($child),
            TrophyMetric::Months->value => $this->months($child),
            TrophyMetric::Streak->value => $this->streak($child),
            TrophyMetric::OnTimeMilestones->value => $this->onTimeSteps($child),
            TrophyMetric::Chapters->value => $this->chapters($child),
            TrophyMetric::Photos->value => $this->photos($child),
            TrophyMetric::Categories->value => $this->categories($child),
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
        $cursor = CarbonImmutable::parse($dates[0]);

        for ($i = 1; $i < $dates->count(); $i++) {
            $next = CarbonImmutable::parse($dates[$i]);

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
     * A milestone caught while the child was actually that age — recorded inside the
     * quarter that opens at the milestone's months_from. Backfilling the first smile
     * at age four is still a memory, just not this trophy.
     */
    public function onTimeSteps(Child $child): int
    {
        return ChildEntry::query()
            ->where('child_entries.child_id', $child->id)
            ->join('child_milestones', 'child_milestones.id', '=', 'child_entries.child_milestone_id')
            ->whereNotNull('child_milestones.months_from')
            ->whereRaw(
                'child_entries.date >= DATE_ADD(?, INTERVAL child_milestones.months_from MONTH)',
                [$child->birthday->toDateString()]
            )
            ->whereRaw(
                'child_entries.date < DATE_ADD(?, INTERVAL child_milestones.months_from + 3 MONTH)',
                [$child->birthday->toDateString()]
            )
            ->count();
    }

    public function chapters(Child $child): int
    {
        return $child->chapters()->whereNotNull('completed_at')->count();
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
            ->join('child_milestones', 'child_milestones.id', '=', 'child_entries.child_milestone_id')
            ->whereNotNull('child_milestones.category_id')
            ->distinct()
            ->count('child_milestones.category_id');
    }
}
