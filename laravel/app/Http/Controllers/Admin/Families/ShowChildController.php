<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Families;

use App\Http\Controllers\Controller;
use App\Support\Admin\ChildSummary;
use App\Support\Progress\Metrics;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The catalogue as this child has walked it. Read-only: nothing on the child
 * pages edits a family's memories.
 */
class ShowChildController extends Controller
{
    public function __invoke(int $child, Metrics $metrics): Response
    {
        $record = ChildSummary::find($child);

        $record->load([
            'chapters' => fn ($q) => $q->orderBy('sort_order')
                ->withCount([
                    'milestones as milestones_total',
                    'milestones as milestones_recorded' => fn ($s) => $s->whereHas('entry'),
                ]),
            'chapters.milestones' => fn ($q) => $q->orderBy('sort_order')->with('entry:id,child_milestone_id,date'),
        ]);

        return Inertia::render('Admin/Children/Show/Journey', [
            ...ChildSummary::for($record, $metrics->for($record)),
            'chapters' => $record->chapters->map(fn ($chapter) => [
                'id' => $chapter->id,
                'name' => $chapter->name,
                'months_from' => $chapter->months_from,
                'xp' => $chapter->xp,
                'completed_at' => $chapter->completed_at?->toIso8601String(),
                'milestones_total' => $chapter->milestones_total,
                'milestones_recorded' => $chapter->milestones_recorded,
                'milestones' => $chapter->milestones->map(fn ($milestone) => [
                    'id' => $milestone->id,
                    'name' => $milestone->name,
                    'happens_after' => $milestone->happens_after,
                    'happens_unit' => $milestone->happens_unit?->value,
                    'xp' => $milestone->xp,
                    'is_custom' => $milestone->milestone_id === null,
                    'is_locked' => $milestone->isLockedFor($record),
                    'recorded_on' => $milestone->entry?->date?->toDateString(),
                ]),
            ]),
        ]);
    }
}
