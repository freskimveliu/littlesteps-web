<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Milestones;

use App\Enums\Icon;
use App\Enums\PropertyKey;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Milestone;
use App\Support\Admin\IndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexMilestonesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = Milestone::query()->with(['chapter:id,name', 'category:id,name,icon,color', 'properties']);

        if ($chapter = $request->integer('chapter')) {
            $query->where('chapter_id', $chapter);
        }

        // sort_order restarts at 10 inside every chapter, so on its own it
        // interleaves the chapters into nonsense. Group by chapter first.
        if (! $request->filled('sort')) {
            $query->orderBy('chapter_id');
        }

        $milestones = IndexQuery::apply(
            $query,
            $request,
            searchable: ['name', 'description'],
            sortable: ['name', 'happens_after', 'xp', 'sort_order'],
        )->paginate(50)->withQueryString();

        return Inertia::render('Admin/Milestones/Index', [
            'milestones' => $milestones,
            'filters' => [...IndexQuery::filters($request), 'chapter' => $request->integer('chapter') ?: null],
            'chapters' => Chapter::orderBy('sort_order')->get(['id', 'name']),
            'categories' => Category::orderBy('sort_order')->get(['id', 'name', 'icon', 'color']),
            'icons' => array_column(Icon::cases(), 'value'),
            'propertyKeys' => array_column(PropertyKey::cases(), 'value'),
        ]);
    }
}
