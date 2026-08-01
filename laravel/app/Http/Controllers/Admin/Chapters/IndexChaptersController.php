<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Chapters;

use App\Enums\Icon;
use App\Http\Controllers\Controller;
use App\Models\TemplateMilestone;
use App\Support\Admin\IndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexChaptersController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $chapters = IndexQuery::apply(
            TemplateMilestone::query()->withCount('steps'),
            $request,
            searchable: ['name', 'description'],
            sortable: ['name', 'months_from', 'xp', 'sort_order', 'steps_count'],
        )->get();

        return Inertia::render('Admin/Chapters/Index', [
            'chapters' => $chapters,
            'filters' => IndexQuery::filters($request),
            'icons' => array_column(Icon::cases(), 'value'),
        ]);
    }
}
