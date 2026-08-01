<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Chapters;

use App\Enums\Icon;
use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Support\Admin\IndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexChaptersController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $chapters = IndexQuery::apply(
            Chapter::query()->withCount('milestones'),
            $request,
            searchable: ['name', 'description'],
            sortable: ['name', 'months_from', 'xp', 'sort_order', 'milestones_count'],
        )->get();

        return Inertia::render('Admin/Chapters/Index', [
            'chapters' => $chapters,
            'filters' => IndexQuery::filters($request),
            'icons' => array_column(Icon::cases(), 'value'),
        ]);
    }
}
