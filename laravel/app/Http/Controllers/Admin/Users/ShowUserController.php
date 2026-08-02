<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\ChildEntry;
use App\Models\User;
use App\Support\Admin\UserSummary;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ShowUserController extends Controller
{
    public function __invoke(int $user): Response
    {
        $parent = UserSummary::find($user);

        return Inertia::render('Admin/Users/Show', [
            'user' => UserSummary::for($parent),
            'activity' => $this->weekly($parent),
            'memoryMix' => $this->memoryMix($parent),
            'contributions' => $this->contributions($parent),
            'recent' => $this->recent($parent),
        ]);
    }

    /**
     * Memories this account wrote in each of the last twelve weeks, counted by the
     * day they sat down and wrote it in their own timezone — not by the day the
     * memory is about, which back-dating would pile onto a week they were quiet.
     *
     * @return array<int, array{label: string, value: int}>
     */
    private function weekly(User $parent, int $weeks = 12): array
    {
        $zone = $parent->timezone ?: 'UTC';
        $start = CarbonImmutable::now($zone)->startOfWeek()->subWeeks($weeks - 1);

        $counts = ChildEntry::where('created_by_user_id', $parent->id)
            ->where('created_at', '>=', $start->utc())
            ->pluck('created_at')
            ->groupBy(fn ($at) => CarbonImmutable::parse($at)->setTimezone($zone)->startOfWeek()->toDateString())
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

    /** @return array<int, array{id: int, name: string, written: int}> */
    private function contributions(User $parent): array
    {
        $written = ChildEntry::where('created_by_user_id', $parent->id)
            ->select('child_id', DB::raw('count(*) as total'))
            ->groupBy('child_id')
            ->pluck('total', 'child_id');

        return $parent->children()
            ->orderBy('birthday')
            ->get()
            ->map(fn (Child $child) => [
                'id' => $child->id,
                'name' => $child->name,
                'written' => (int) $written->get($child->id, 0),
            ])
            ->all();
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
