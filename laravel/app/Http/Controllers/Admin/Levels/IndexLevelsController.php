<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Levels;

use App\Enums\Icon;
use App\Http\Controllers\Controller;
use App\Models\TemplateLevel;
use App\Support\Admin\IndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexLevelsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $levels = IndexQuery::apply(
            TemplateLevel::query(),
            $request,
            searchable: ['name'],
            sortable: ['name', 'min_xp'],
            defaultSort: 'min_xp',
        )->get();

        return Inertia::render('Admin/Levels/Index', [
            'levels' => $levels,
            'filters' => IndexQuery::filters($request),
            'icons' => array_column(Icon::cases(), 'value'),
        ]);
    }
}
