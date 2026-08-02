<?php

declare(strict_types=1);

namespace App\Support\Progress;

use App\Enums\TrophyMetric;
use App\Models\Child;
use App\Models\ChildEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The numbers every trophy rule reads.
 *
 * Each one is gated by the calendar rather than by typing speed: the catalogue
 * holds a finite number of milestones, so a raw entry count would let a determined
 * parent clear the whole ladder in a fortnight.
 *
 * The days a parent showed up are read off created_at in their own timezone,
 * never off the memory's date — otherwise an afternoon of back-dating would
 * hand over a week, a month and a streak all at once.
 */
class Metrics
{
    /** @return array<string, int> */
    public function for(Child $child): array
    {
        $days = $this->recordedDays($child);

        return [
            TrophyMetric::Days->value => $days->count(),
            TrophyMetric::Months->value => $this->distinctMonths($days),
            TrophyMetric::Streak->value => $this->longestRunTo($days),
            TrophyMetric::OnTimeMilestones->value => $this->onTimeSteps($child),
            TrophyMetric::Chapters->value => $this->chapters($child),
            TrophyMetric::Photos->value => $this->photos($child),
            TrophyMetric::Categories->value => $this->categories($child),
        ];
    }

    public function days(Child $child): int
    {
        return $this->recordedDays($child)->count();
    }

    public function months(Child $child): int
    {
        return $this->distinctMonths($this->recordedDays($child));
    }

    public function streak(Child $child): int
    {
        return $this->longestRunTo($this->recordedDays($child));
    }

    /**
     * The distinct days a memory was written, newest first, on the calendar of the
     * account the child lives under — one clock for the child, so a grandparent
     * recording from another timezone cannot split a day in two.
     *
     * @return Collection<int, string>
     */
    private function recordedDays(Child $child): Collection
    {
        $zone = $child->creator()->value('timezone') ?: 'UTC';

        return $child->entries()
            ->orderByDesc('created_at')
            ->pluck('created_at')
            ->map(fn ($at) => CarbonImmutable::parse($at)->setTimezone($zone)->toDateString())
            ->unique()
            ->values();
    }

    /** @param Collection<int, string> $days */
    private function distinctMonths(Collection $days): int
    {
        return $days->map(fn (string $day) => substr($day, 0, 7))->unique()->count();
    }

    /**
     * Consecutive days ending at the day the parent last wrote something down —
     * the run is judged where it ended, so an old week of seven still counts as one.
     *
     * @param  Collection<int, string>  $days
     */
    private function longestRunTo(Collection $days): int
    {
        if ($days->isEmpty()) {
            return 0;
        }

        $streak = 1;
        $cursor = CarbonImmutable::parse($days[0]);

        for ($i = 1; $i < $days->count(); $i++) {
            $next = CarbonImmutable::parse($days[$i]);

            if (! $cursor->subDay()->isSameDay($next)) {
                break;
            }

            $streak++;
            $cursor = $next;
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

    /** Trophies are awarded for photos kept, so this stays images even as a memory may carry more. */
    public function photos(Child $child): int
    {
        return DB::table('media')
            ->where('model_type', ChildEntry::class)
            ->where('collection_name', ChildEntry::MEDIA)
            ->where('mime_type', 'like', 'image/%')
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
