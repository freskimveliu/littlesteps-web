import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

export type SortOrder = 'asc' | 'desc';

interface Options {
    url: string;
    search?: string | null;
    sortKey?: string | null;
    sortOrder?: SortOrder | null;
    extra?: Record<string, unknown>;
}

/**
 * The Inertia equivalent of cocon's useDebouncedListLoader: search and sort
 * live in the query string and the server does the filtering, so a reload or
 * a shared link lands on the same view.
 */
export function useIndexFilters(options: Options) {
    const search = ref(options.search ?? '');
    const sortKey = ref<string | null>(options.sortKey ?? null);
    const sortOrder = ref<SortOrder | null>(options.sortOrder ?? null);
    const searching = ref(false);

    let debounce: ReturnType<typeof setTimeout> | null = null;

    function visit(extra: Record<string, unknown> = {}) {
        router.get(
            options.url,
            {
                ...(options.extra ?? {}),
                ...extra,
                search: search.value || undefined,
                sort: sortKey.value || undefined,
                order: sortOrder.value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onStart: () => (searching.value = true),
                onFinish: () => (searching.value = false),
            },
        );
    }

    watch(search, () => {
        if (debounce) clearTimeout(debounce);
        debounce = setTimeout(visit, 300);
    });

    function toggleSort(key: string, order: SortOrder) {
        sortKey.value = key;
        sortOrder.value = order;
        visit();
    }

    return { search, searching, sortKey, sortOrder, toggleSort, visit };
}
