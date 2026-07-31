<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Badges;

use App\Enums\AchievementMetric;
use App\Enums\Icon;
use App\Enums\RewardType;
use App\Http\Controllers\Controller;
use App\Models\TemplateAchievement;
use App\Support\Admin\IndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexBadgesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $badges = IndexQuery::apply(
            TemplateAchievement::query(),
            $request,
            searchable: ['name', 'slug', 'description'],
            sortable: ['name', 'metric', 'threshold', 'xp', 'sort_order'],
        )->get();

        return Inertia::render('Admin/Badges/Index', [
            'badges' => $badges,
            'filters' => IndexQuery::filters($request),
            'metrics' => array_column(AchievementMetric::cases(), 'value'),
            'rewards' => array_column(RewardType::cases(), 'value'),
            'icons' => array_column(Icon::cases(), 'value'),
        ]);
    }
}
