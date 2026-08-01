<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Families;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Support\Admin\IndexQuery;
use App\Support\Progress\LevelLadder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexChildrenController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $children = IndexQuery::apply(
            Child::query()
                ->with('creator:id,name,email')
                ->withCount(['entries', 'achievements', 'chapters as chapters_done_count' => fn ($q) => $q->whereNotNull('completed_at')]),
            $request,
            searchable: ['name'],
            sortable: ['name', 'birthday', 'xp', 'entries_count', 'achievements_count', 'created_at'],
            defaultSort: 'created_at',
        )->paginate(40)->withQueryString();

        $children->getCollection()->transform(fn (Child $child) => [
            'id' => $child->id,
            'name' => $child->name,
            'birthday' => $child->birthday->toDateString(),
            'age_months' => $child->ageInMonths(),
            'gender' => $child->gender,
            'xp' => $child->xp,
            'level' => LevelLadder::for($child->xp)['level'],
            'level_name' => LevelLadder::for($child->xp)['name'],
            'entries_count' => $child->entries_count,
            'achievements_count' => $child->achievements_count,
            'chapters_done_count' => $child->chapters_done_count,
            'creator' => $child->creator?->only(['id', 'name', 'email']),
        ]);

        return Inertia::render('Admin/Children/Index', [
            'children' => $children,
            'filters' => IndexQuery::filters($request),
        ]);
    }
}
