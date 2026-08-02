<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Families;

use App\Http\Controllers\Controller;
use App\Models\ChildTrophy;
use App\Models\Trophy;
use App\Support\Admin\ChildSummary;
use App\Support\Progress\Metrics;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ShowChildTrophiesController extends Controller
{
    public function __invoke(int $child, Metrics $metrics): Response
    {
        $record = ChildSummary::find($child);
        $counts = $metrics->for($record);

        return Inertia::render('Admin/Children/Show/Trophies', [
            ...ChildSummary::for($record, $counts),
            'trophies' => $this->trophies($counts, $record->trophies()->get()->keyBy('trophy_id')),
        ]);
    }

    /**
     * The catalogue as it stands, plus anything this child holds that has since
     * left it — those read from the copy taken when they were unlocked, which is
     * the only record of what the trophy said at the time.
     *
     * @param  array<string, int>  $counts
     * @param  Collection<int|null, ChildTrophy>  $held
     * @return Collection<int, array<string, mixed>>
     */
    private function trophies(array $counts, $held)
    {
        $catalogue = Trophy::query()
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Trophy $trophy) => [
                'id' => $trophy->id,
                'name' => $trophy->name,
                'metric' => $trophy->metric,
                'threshold' => $trophy->threshold,
                'reward' => $trophy->reward,
                'progress' => min($counts[$trophy->metric->value] ?? 0, $trophy->threshold),
                'unlocked_at' => $held->get($trophy->id)?->unlocked_at?->toIso8601String(),
                'is_retired' => false,
            ]);

        $listed = $catalogue->pluck('id');

        $retired = $held
            ->reject(fn ($earned) => $listed->contains($earned->trophy_id))
            ->values()
            ->map(fn ($earned) => [
                'id' => $earned->trophy_id ?? -$earned->id,
                'name' => $earned->name,
                'metric' => $earned->metric,
                'threshold' => $earned->threshold,
                'reward' => $earned->reward,
                'progress' => $earned->threshold,
                'unlocked_at' => $earned->unlocked_at?->toIso8601String(),
                'is_retired' => true,
            ]);

        return $catalogue->concat($retired)->values();
    }
}
