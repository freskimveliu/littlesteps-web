<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Admin\IndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexUsersController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = User::query()
            ->withTrashed()
            ->withCount(['ownedChildren', 'children', 'devices']);

        if ($request->query('filter') === 'admins') {
            $query->where('is_admin', true);
        }

        if ($request->query('filter') === 'guests') {
            $query->whereNull('email');
        }

        if ($request->query('filter') === 'deleted') {
            $query->whereNotNull('deleted_at');
        }

        $users = IndexQuery::apply(
            $query,
            $request,
            searchable: ['name', 'email'],
            sortable: ['name', 'email', 'current_streak', 'longest_streak', 'last_entry_date', 'created_at'],
            defaultSort: 'created_at',
            defaultOrder: 'desc',
        )->paginate(40)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [...IndexQuery::filters($request), 'filter' => $request->query('filter')],
            'counts' => [
                'all' => User::withTrashed()->count(),
                'admins' => User::where('is_admin', true)->count(),
                'guests' => User::whereNull('email')->count(),
                'deleted' => User::onlyTrashed()->count(),
            ],
            'timezone' => config('app.timezone'),
        ]);
    }
}
