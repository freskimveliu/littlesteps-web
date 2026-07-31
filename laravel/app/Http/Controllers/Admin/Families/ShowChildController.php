<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Families;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\TemplateAchievement;
use App\Support\Progress\Level;
use App\Support\Progress\Metrics;
use Inertia\Inertia;
use Inertia\Response;

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
            'milestones' => fn ($q) => $q->orderBy('sort_order')
                ->withCount([
                    'steps as steps_total' => fn ($s) => $s->where('is_hidden', false),
                    'steps as steps_recorded' => fn ($s) => $s->where('is_hidden', false)->whereHas('entry'),
                ]),
            'achievements.achievement',
            'rewards.childAchievement.achievement',
        ])->findOrFail($child);

        $counts = $metrics->for($record);

        $entries = $record->entries()
            ->with(['step:id,name', 'properties', 'media'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn ($entry) => [
                'id' => $entry->id,
                'step' => $entry->step?->name,
                'description' => $entry->description,
                'date' => $entry->date->toDateString(),
                'mood' => $entry->mood,
                'is_free' => $entry->isFree(),
                'photos' => $entry->getMedia('photos')->count(),
                'properties' => $entry->properties->map(fn ($p) => [
                    'label' => $p->name ?? ucfirst($p->key->value),
                    'value' => $p->value,
                    'unit' => $p->key->unit(),
                ]),
                'created_at' => $entry->created_at?->toIso8601String(),
            ]);

        $held = $record->achievements->keyBy('template_achievement_id');

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
            'level' => Level::for($record->xp),
            'levelCount' => Level::total(),
            'metrics' => $counts,
            'members' => $record->memberships->map(fn ($m) => [
                'id' => $m->id,
                'user' => $m->user?->only(['id', 'name', 'email']),
                'relation' => $m->relation,
                'role' => $m->role,
                'is_creator' => $m->user_id === $record->created_by_user_id,
            ]),
            'chapters' => $record->milestones->map(fn ($chapter) => [
                'id' => $chapter->id,
                'name' => $chapter->name,
                'months_from' => $chapter->months_from,
                'xp' => $chapter->xp,
                'is_hidden' => $chapter->is_hidden,
                'completed_at' => $chapter->completed_at?->toIso8601String(),
                'steps_total' => $chapter->steps_total,
                'steps_recorded' => $chapter->steps_recorded,
            ]),
            'badges' => TemplateAchievement::query()
                ->active()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (TemplateAchievement $badge) => [
                    'id' => $badge->id,
                    'name' => $badge->name,
                    'metric' => $badge->metric,
                    'threshold' => $badge->threshold,
                    'reward' => $badge->reward,
                    'progress' => min($counts[$badge->metric->value] ?? 0, $badge->threshold),
                    'unlocked_at' => $held->get($badge->id)?->unlocked_at?->toIso8601String(),
                ]),
            'rewards' => $record->rewards->map(fn ($reward) => [
                'id' => $reward->id,
                'type' => $reward->type,
                'status' => $reward->status,
                'badge' => $reward->childAchievement?->achievement?->name,
                'claimed_at' => $reward->claimed_at?->toIso8601String(),
                'generated_at' => $reward->generated_at?->toIso8601String(),
                'has_content' => $reward->content !== null,
            ]),
            'entries' => $entries,
            'entriesTotal' => $record->entries()->count(),
        ]);
    }
}
