<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Steps;

use App\Enums\Icon;
use App\Enums\PropertyKey;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\TemplateMilestone;
use App\Models\TemplateStep;
use App\Support\Admin\IndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexStepsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = TemplateStep::query()->with(['milestone:id,name', 'category:id,name,icon,color', 'properties']);

        if ($chapter = $request->integer('chapter')) {
            $query->where('template_milestone_id', $chapter);
        }

        // sort_order restarts at 10 inside every chapter, so on its own it
        // interleaves the chapters into nonsense. Group by chapter first.
        if (! $request->filled('sort')) {
            $query->orderBy('template_milestone_id');
        }

        $steps = IndexQuery::apply(
            $query,
            $request,
            searchable: ['name', 'description'],
            sortable: ['name', 'months_from', 'xp', 'sort_order'],
        )->paginate(50)->withQueryString();

        return Inertia::render('Admin/Steps/Index', [
            'steps' => $steps,
            'filters' => [...IndexQuery::filters($request), 'chapter' => $request->integer('chapter') ?: null],
            'chapters' => TemplateMilestone::orderBy('sort_order')->get(['id', 'name']),
            'categories' => Category::orderBy('sort_order')->get(['id', 'name', 'icon', 'color']),
            'icons' => array_column(Icon::cases(), 'value'),
            'propertyKeys' => array_column(PropertyKey::cases(), 'value'),
        ]);
    }
}
