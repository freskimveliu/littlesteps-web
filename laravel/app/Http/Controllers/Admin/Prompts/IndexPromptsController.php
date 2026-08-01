<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Prompts;

use App\Enums\Icon;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Prompt;
use App\Support\Admin\IndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexPromptsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $prompts = IndexQuery::apply(
            Prompt::query()->with('category:id,name,color'),
            $request,
            searchable: ['name'],
            sortable: ['name', 'months_from', 'months_to', 'sort_order'],
        )->paginate(50)->withQueryString();

        return Inertia::render('Admin/Prompts/Index', [
            'prompts' => $prompts,
            'filters' => IndexQuery::filters($request),
            'categories' => Category::orderBy('sort_order')->get(['id', 'name', 'color']),
            'icons' => array_column(Icon::cases(), 'value'),
        ]);
    }
}
