<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Categories;

use App\Enums\Icon;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\Admin\IndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexCategoriesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $categories = IndexQuery::apply(
            Category::query()->withCount('templateSteps'),
            $request,
            searchable: ['name', 'description'],
            sortable: ['name', 'sort_order', 'template_steps_count'],
        )->get();

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'filters' => IndexQuery::filters($request),
            'icons' => array_column(Icon::cases(), 'value'),
        ]);
    }
}
