<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Categories;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;

class DestroyCategoryController extends Controller
{
    public function __invoke(Category $category): RedirectResponse
    {
        if ($category->milestones()->exists()) {
            return back()->with('error', 'Milestones still use this category. Move them first.');
        }

        $category->delete();

        return back()->with('success', 'Category removed.');
    }
}
