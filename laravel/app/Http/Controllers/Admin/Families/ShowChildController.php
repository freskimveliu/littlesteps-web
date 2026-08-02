<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Families;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\ChildEntry;
use App\Models\ChildTrophy;
use App\Models\Trophy;
use App\Support\Progress\LevelLadder;
use App\Support\Progress\Metrics;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The whole of one child's journey on a single page — what support needs when
 * a parent writes in. Read-only: nothing here edits a family's memories.
 */
class ShowChildController extends Controller
{
    public function __invoke(int $child, Metrics $metrics): Response
    {
        $record = Child::with([
            'creator:id,name,email',
            'memberships.user:id,name,email',
            'chapters' => fn ($q) => $q->orderBy('sort_order')
                ->withCount([
                    'milestones as milestones_total' => fn ($s) => $s->where('is_hidden', false),
                    'milestones as milestones_recorded' => fn ($s) => $s->where('is_hidden', false)->whereHas('entry'),
                ]),
            'chapters.milestones' => fn ($q) => $q->orderBy('sort_order')->with('entry:id,child_milestone_id,date'),
            'trophies',
            'rewards.childTrophy',
        ])->findOrFail($child);

        $counts = $metrics->for($record);

        $entries = $record->entries()
            ->with([
                'milestone:id,name,child_chapter_id',
                'milestone.chapter:id,name',
                'creator:id,name,email',
                'properties',
                'media',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->each->bindMediaOwner()
            ->map(fn (ChildEntry $entry) => [
                'id' => $entry->id,
                'milestone' => $entry->milestone?->name,
                'chapter' => $entry->milestone?->chapter?->name,
                'description' => $entry->description,
                'date' => $entry->date->toDateString(),
                'mood' => $entry->mood,
                'is_free' => $entry->isFree(),
                'author' => $entry->creator?->only(['id', 'name', 'email']),
                'media' => $entry->getMedia(ChildEntry::MEDIA)->map(fn (Media $media) => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'mime' => $media->mime_type,
                    'size' => $media->size,
                    'thumb' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
                    'display' => $media->hasGeneratedConversion('display') ? $media->getUrl('display') : $media->getUrl(),
                    'original' => $media->getUrl(),
                ])->values(),
                'properties' => $entry->properties->map(fn ($p) => [
                    'label' => $p->name ?? ucfirst($p->key->value),
                    'value' => $p->value,
                    'unit' => $p->key->unit(),
                ]),
                'created_at' => $entry->created_at?->toIso8601String(),
                'updated_at' => $entry->updated_at?->toIso8601String(),
            ]);

        $held = $record->trophies->keyBy('trophy_id');

        return Inertia::render('Admin/Children/Show', [
            'child' => [
                'id' => $record->id,
                'name' => $record->name,
                'birthday' => $record->birthday->toDateString(),
                'age_months' => $record->ageInMonths(),
                'gender' => $record->gender,
                'xp' => $record->xp,
                'photo' => $record->photoThumbUrl(),
                'created_at' => $record->created_at?->toIso8601String(),
                'creator' => $record->creator?->only(['id', 'name', 'email']),
            ],
            'level' => LevelLadder::for($record->xp),
            'levelCount' => LevelLadder::total(),
            'metrics' => $counts,
            'members' => $record->memberships->map(fn ($m) => [
                'id' => $m->id,
                'user' => $m->user?->only(['id', 'name', 'email']),
                'relation' => $m->relation,
                'role' => $m->role,
                'is_creator' => $m->user_id === $record->created_by_user_id,
            ]),
            'chapters' => $record->chapters->map(fn ($chapter) => [
                'id' => $chapter->id,
                'name' => $chapter->name,
                'months_from' => $chapter->months_from,
                'xp' => $chapter->xp,
                'is_hidden' => $chapter->is_hidden,
                'completed_at' => $chapter->completed_at?->toIso8601String(),
                'milestones_total' => $chapter->milestones_total,
                'milestones_recorded' => $chapter->milestones_recorded,
                'milestones' => $chapter->milestones->map(fn ($milestone) => [
                    'id' => $milestone->id,
                    'name' => $milestone->name,
                    'months_from' => $milestone->months_from,
                    'xp' => $milestone->xp,
                    'is_hidden' => $milestone->is_hidden,
                    'is_custom' => $milestone->milestone_id === null,
                    'is_locked' => $milestone->isLockedFor($record),
                    'recorded_on' => $milestone->entry?->date?->toDateString(),
                ]),
            ]),
            'trophies' => $this->trophies($counts, $held),
            'rewards' => $record->rewards->map(fn ($reward) => [
                'id' => $reward->id,
                'type' => $reward->type,
                'status' => $reward->status,
                'trophy' => $reward->childTrophy?->name,
                'claimed_at' => $reward->claimed_at?->toIso8601String(),
                'generated_at' => $reward->generated_at?->toIso8601String(),
                'has_content' => $reward->content !== null,
            ]),
            'entries' => $entries,
            'entriesTotal' => $record->entries()->count(),
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
