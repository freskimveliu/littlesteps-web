<?php

declare(strict_types=1);

namespace App\Support\Admin;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Search and sort live in the query string, so the server does the filtering
 * and a shared admin link lands on the same view.
 *
 * `apply()` decides what the sort actually is and records it on the request;
 * `filters()` reads that back rather than working it out again, so the arrow the
 * table draws is always the order the rows are really in.
 */
class IndexQuery
{
    private const RESOLVED = 'admin.index-query';

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
        string $defaultOrder = 'asc',
    ): Builder {
        $search = trim((string) $request->query('search', ''));

        if ($search !== '' && $searchable !== []) {
            $query->where(function (Builder $q) use ($searchable, $search) {
                foreach ($searchable as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        $asked = (string) $request->query('sort', $defaultSort);
        $sort = in_array($asked, $sortable, true) ? $asked : $defaultSort;
        $order = self::order($request, $defaultOrder);

        $request->attributes->set(self::RESOLVED, ['sort' => $sort, 'order' => $order]);

        return $query->orderBy($sort, $order);
    }

    /**
     * What the table is actually showing. Null until apply() has run.
     *
     * @return array{search: string|null, sort: string|null, order: string|null}
     */
    public static function filters(Request $request): array
    {
        /** @var array{sort: string, order: string}|null $resolved */
        $resolved = $request->attributes->get(self::RESOLVED);

        return [
            'search' => $request->query('search'),
            'sort' => $resolved['sort'] ?? null,
            'order' => $resolved['order'] ?? null,
        ];
    }

    private static function order(Request $request, string $defaultOrder): string
    {
        return match ($request->query('order')) {
            'asc' => 'asc',
            'desc' => 'desc',
            default => $defaultOrder,
        };
    }
}
