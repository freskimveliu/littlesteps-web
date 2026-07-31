<?php

declare(strict_types=1);

namespace App\Support\Admin;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Search and sort live in the query string, so the server does the filtering
 * and a shared admin link lands on the same view.
 */
class IndexQuery
{
    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  array<int, string>  $searchable
     * @param  array<int, string>  $sortable
     */
    public static function apply(
        Builder $query,
        Request $request,
        array $searchable,
        array $sortable,
        string $defaultSort = 'sort_order',
    ): Builder {
        $search = trim((string) $request->query('search', ''));

        if ($search !== '' && $searchable !== []) {
            $query->where(function (Builder $q) use ($searchable, $search) {
                foreach ($searchable as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        $sort = (string) $request->query('sort', $defaultSort);
        $order = $request->query('order') === 'desc' ? 'desc' : 'asc';

        $query->orderBy(in_array($sort, $sortable, true) ? $sort : $defaultSort, $order);

        return $query;
    }

    /** @return array{search: string|null, sort: string|null, order: string|null} */
    public static function filters(Request $request): array
    {
        return [
            'search' => $request->query('search'),
            'sort' => $request->query('sort'),
            'order' => $request->query('order'),
        ];
    }
}
