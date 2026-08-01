<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\RewardStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Child;
use App\Models\ChildEntry;
use App\Models\ChildReward;
use App\Models\Level;
use App\Models\Milestone;
use App\Models\Prompt;
use App\Models\Trophy;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'catalogue' => [
                ['label' => 'Chapters', 'value' => Chapter::count(), 'href' => '/admin/chapters'],
                ['label' => 'Milestones', 'value' => Milestone::count(), 'href' => '/admin/milestones'],
                ['label' => 'Categories', 'value' => Category::count(), 'href' => '/admin/categories'],
                ['label' => 'Trophies', 'value' => Trophy::count(), 'href' => '/admin/trophies'],
                ['label' => 'Levels', 'value' => Level::count(), 'href' => '/admin/levels'],
                ['label' => 'Prompts', 'value' => Prompt::count(), 'href' => '/admin/prompts'],
            ],
            'usage' => [
                ['label' => 'Users', 'value' => User::count(), 'href' => '/admin/users'],
                ['label' => 'Children', 'value' => Child::count(), 'href' => '/admin/children'],
                ['label' => 'Memories', 'value' => ChildEntry::count(), 'href' => null],
                ['label' => 'Gifts earned', 'value' => ChildReward::count(), 'href' => '/admin/gifts'],
            ],
            'signups' => $this->weekly(User::query()),
            'memories' => $this->weekly(ChildEntry::query()),
            'memoryMix' => $this->memoryMix(),
            'giftStatus' => $this->giftStatus(),
            'milestonesPerChapter' => Chapter::query()
                ->withCount('milestones')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Chapter $m) => [
                    'label' => $m->name,
                    'value' => $m->milestones_count,
                ]),
            'gifts' => Trophy::whereNotNull('reward')->orderBy('sort_order')->get([
                'id', 'name', 'reward', 'metric', 'threshold',
            ]),
        ]);
    }

    /**
     * Rows created in each of the last twelve weeks.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return array<int, array{label: string, value: int}>
     */
    private function weekly(Builder $query, int $weeks = 12): array
    {
        $start = Carbon::now()->startOfWeek()->subWeeks($weeks - 1);

        $counts = $query
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn ($row) => Carbon::parse($row->created_at)->startOfWeek()->toDateString())
            ->map->count();

        return collect(range(0, $weeks - 1))
            ->map(function (int $i) use ($start, $counts) {
                $week = $start->copy()->addWeeks($i);

                return [
                    'label' => $week->format('j M'),
                    'value' => $counts->get($week->toDateString(), 0),
                ];
            })
            ->all();
    }

    /** @return array<int, array{label: string, value: int, color: string}> */
    private function memoryMix(): array
    {
        $free = ChildEntry::free()->count();
        $milestone = ChildEntry::whereNotNull('child_milestone_id')->count();

        return [
            ['label' => 'From a milestone', 'value' => $milestone, 'color' => '#7E5EBF'],
            ['label' => 'Free memories', 'value' => $free, 'color' => '#FFE5A0'],
        ];
    }

    /** @return array<int, array{label: string, value: int, color: string}> */
    private function giftStatus(): array
    {
        $counts = ChildReward::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $colors = [
            RewardStatus::Unclaimed->value => '#CBD5E1',
            RewardStatus::Generating->value => '#7E5EBF',
            RewardStatus::Ready->value => '#34D399',
            RewardStatus::Failed->value => '#EF4444',
        ];

        return collect(RewardStatus::cases())
            ->map(fn (RewardStatus $status) => [
                'label' => ucfirst($status->value),
                'value' => (int) $counts->get($status->value, 0),
                'color' => $colors[$status->value],
            ])
            ->all();
    }
}
