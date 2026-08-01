<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Child;
use App\Models\ChildEntry;
use App\Models\User;
use App\Support\Progress\LevelLadder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ShowUserController extends Controller
{
    public function __invoke(int $user): Response
    {
        $parent = User::withTrashed()
            ->with([
                'children' => fn ($q) => $q->withCount([
                    'entries',
                    'trophies',
                    'chapters as chapters_done_count' => fn ($c) => $c->whereNotNull('completed_at'),
                ])->orderBy('birthday'),
                'devices',
                'settings',
            ])
            ->findOrFail($user);

        $writtenPerChild = ChildEntry::where('created_by_user_id', $parent->id)
            ->select('child_id', DB::raw('count(*) as total'))
            ->groupBy('child_id')
            ->pluck('total', 'child_id');

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $parent->id,
                'name' => $parent->name,
                'email' => $parent->email,
                'timezone' => $parent->timezone,
                'language' => $parent->language,
                'is_admin' => $parent->is_admin,
                'is_registered' => $parent->isRegistered(),
                'current_streak' => $parent->current_streak,
                'longest_streak' => $parent->longest_streak,
                'last_entry_date' => $parent->last_entry_date?->toDateString(),
                'deleted_at' => $parent->deleted_at?->toIso8601String(),
                'created_at' => $parent->created_at?->toIso8601String(),
                'photo' => $parent->photoThumbUrl(),
                'settings' => $parent->settingsMap(),
            ],
            'children' => $parent->children->map(function (Child $child) use ($parent, $writtenPerChild) {
                $level = LevelLadder::for($child->xp);

                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'birthday' => $child->birthday->toDateString(),
                    'age_months' => $child->ageInMonths(),
                    'gender' => $child->gender,
                    'xp' => $child->xp,
                    'photo' => $child->photoThumbUrl(),
                    'level' => $level['level'],
                    'level_name' => $level['name'],
                    'level_progress' => $level['progress'],
                    'xp_to_next' => $level['xp_to_next'],
                    'entries_count' => $child->entries_count,
                    'trophies_count' => $child->trophies_count,
                    'chapters_done_count' => $child->chapters_done_count,
                    'written_here' => (int) $writtenPerChild->get($child->id, 0),
                    'is_owner' => $child->created_by_user_id === $parent->id,
                    'role' => $child->pivot->role,
                    'relation' => $child->pivot->relation,
                ];
            }),
            'devices' => $parent->devices,
            'written' => ChildEntry::where('created_by_user_id', $parent->id)->count(),
            'photos' => DB::table('media')
                ->where('model_type', ChildEntry::class)
                ->where('collection_name', ChildEntry::MEDIA)
                ->where('mime_type', 'like', 'image/%')
                ->whereIn('model_id', ChildEntry::where('created_by_user_id', $parent->id)->select('id'))
                ->count(),
            'chapterCount' => Chapter::count(),
            'activity' => $this->weekly($parent),
            'memoryMix' => $this->memoryMix($parent),
            'recent' => $this->recent($parent),
        ]);
    }

    /**
     * Memories this account wrote in each of the last twelve weeks, counted by the
     * day the memory belongs to rather than the day it was typed.
     *
     * @return array<int, array{label: string, value: int}>
     */
    private function weekly(User $parent, int $weeks = 12): array
    {
        $start = Carbon::now()->startOfWeek()->subWeeks($weeks - 1);

        $counts = ChildEntry::where('created_by_user_id', $parent->id)
            ->where('date', '>=', $start->toDateString())
            ->get(['date'])
            ->groupBy(fn (ChildEntry $entry) => $entry->date->copy()->startOfWeek()->toDateString())
            ->map->count();

        return collect(range(0, $weeks - 1))
            ->map(function (int $i) use ($start, $counts) {
                $week = $start->copy()->addWeeks($i);

                return [
                    'label' => $week->format('j M'),
                    'value' => (int) $counts->get($week->toDateString(), 0),
                ];
            })
            ->all();
    }

    /** @return array<int, array{label: string, value: int, color: string}> */
    private function memoryMix(User $parent): array
    {
        $milestone = ChildEntry::where('created_by_user_id', $parent->id)
            ->whereNotNull('child_milestone_id')
            ->count();

        $free = ChildEntry::where('created_by_user_id', $parent->id)->free()->count();

        return [
            ['label' => 'From a milestone', 'value' => $milestone, 'color' => '#7E5EBF'],
            ['label' => 'Free memories', 'value' => $free, 'color' => '#FFE5A0'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function recent(User $parent, int $limit = 8): array
    {
        return ChildEntry::where('created_by_user_id', $parent->id)
            ->with(['child:id,name', 'milestone:id,name', 'media'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (ChildEntry $entry) => [
                'id' => $entry->id,
                'child' => $entry->child?->only(['id', 'name']),
                'milestone' => $entry->milestone?->name,
                'description' => $entry->description,
                'date' => $entry->date->toDateString(),
                'mood' => $entry->mood,
                'media' => $entry->mediaCount(),
            ])
            ->all();
    }
}
