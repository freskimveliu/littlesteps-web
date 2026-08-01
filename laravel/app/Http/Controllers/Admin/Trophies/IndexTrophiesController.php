<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Trophies;

use App\Enums\Icon;
use App\Enums\RewardType;
use App\Enums\TrophyMetric;
use App\Http\Controllers\Controller;
use App\Models\Trophy;
use App\Support\Admin\IndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexTrophiesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $trophies = IndexQuery::apply(
            Trophy::query(),
            $request,
            searchable: ['name', 'description'],
            sortable: ['name', 'metric', 'threshold', 'xp', 'sort_order'],
        )->get();

        return Inertia::render('Admin/Trophies/Index', [
            'trophies' => $trophies,
            'filters' => IndexQuery::filters($request),
            'metrics' => array_column(TrophyMetric::cases(), 'value'),
            'rewards' => array_column(RewardType::cases(), 'value'),
            'icons' => array_column(Icon::cases(), 'value'),
        ]);
    }
}
