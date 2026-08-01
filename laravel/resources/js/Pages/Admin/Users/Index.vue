<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import UiPageHeader from '../../../components/ui/UiPageHeader.vue';
import UiTable from '../../../components/ui/UiTable.vue';
import UiTableRow from '../../../components/ui/UiTableRow.vue';
import UiTableCell from '../../../components/ui/UiTableCell.vue';
import UiTableHeader from '../../../components/ui/UiTableHeader.vue';
import UiSortableTableHeader from '../../../components/ui/UiSortableTableHeader.vue';
import UiSearchInput from '../../../components/ui/UiSearchInput.vue';
import UiButton from '../../../components/ui/UiButton.vue';
import UiSpinner from '../../../components/ui/UiSpinner.vue';
import UiBadge from '../../../components/ui/UiBadge.vue';
import UiPagination from '../../../components/ui/UiPagination.vue';
import UiFilterPopover from '../../../components/ui/UiFilterPopover.vue';
import UiSelect from '../../../components/ui/UiSelect.vue';
import { useIndexFilters } from '../../../composables/useIndexFilters';

interface Parent {
    id: number;
    name: string;
    email: string | null;
    is_admin: boolean;
    current_streak: number;
    longest_streak: number;
    last_entry_date: string | null;
    deleted_at: string | null;
    owned_children_count: number;
    children_count: number;
    devices_count: number;
}

const props = defineProps<{
    users: { data: Parent[]; current_page: number; last_page: number; total: number; per_page: number };
    filters: { search: string | null; sort: string | null; order: string | null; filter: string | null };
    counts: { all: number; admins: number; guests: number; deleted: number };
}>();

const { search, searching, sortKey, sortOrder, toggleSort, visit } = useIndexFilters({
    url: '/admin/users',
    search: props.filters.search,
    sortKey: props.filters.sort,
    sortOrder: props.filters.order as 'asc' | 'desc' | null,
    extra: { filter: props.filters.filter ?? undefined },
});

const groupOptions = [
    { value: '', label: `Everyone (${props.counts.all})` },
    { value: 'admins', label: `Admins (${props.counts.admins})` },
    { value: 'guests', label: `Not signed up (${props.counts.guests})` },
    { value: 'deleted', label: `Deleting (${props.counts.deleted})` },
];

const group = ref(props.filters.filter ?? '');

const activeCount = computed(() => (props.filters.filter ? 1 : 0));

function applyFilters() {
    router.get('/admin/users', { filter: group.value || undefined }, { preserveState: false });
}

function resetFilters() {
    group.value = '';
    router.get('/admin/users', {}, { preserveState: false });
}
</script>

<template>
    <Head title="Parents" />

    <AdminLayout>
        <UiPageHeader title="Parents" />

        <UiTable :empty="users.data.length === 0" empty-title="No accounts match">
            <template #toolbar>
                <UiSpinner v-if="searching" size="xs" tone="primary" class="mr-2" />
                <UiSearchInput v-model="search" placeholder="Name or email…" />
                <UiFilterPopover
                    :active-count="activeCount"
                    title="Filters"
                    @apply="applyFilters"
                    @reset="resetFilters"
                >
                    <UiSelect v-model="group" label="Show" :options="groupOptions" />
                </UiFilterPopover>
            </template>

            <template #header>
                <UiSortableTableHeader sort-key="name" :active-key="sortKey" :active-order="sortOrder" @sort="toggleSort">
                    Name
                </UiSortableTableHeader>
                <UiSortableTableHeader sort-key="email" :active-key="sortKey" :active-order="sortOrder" @sort="toggleSort">
                    Email
                </UiSortableTableHeader>
                <UiTableHeader align="right">Children</UiTableHeader>
                <UiSortableTableHeader
                    sort-key="current_streak"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Streak
                </UiSortableTableHeader>
                <UiSortableTableHeader
                    sort-key="last_entry_date"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Last memory
                </UiSortableTableHeader>
                <UiTableHeader align="right" />
            </template>

            <template #body>
                <UiTableRow v-for="parent in users.data" :key="parent.id">
                    <UiTableCell>
                        <div class="flex items-center gap-2">
                            <Link :href="`/admin/users/${parent.id}`" class="font-medium text-slate-900 hover:underline">
                                {{ parent.name }}
                            </Link>
                            <UiBadge v-if="parent.is_admin" tone="primary">admin</UiBadge>
                            <UiBadge v-if="parent.deleted_at" tone="danger">deleting</UiBadge>
                        </div>
                    </UiTableCell>
                    <UiTableCell cell-class="text-slate-500">
                        <span v-if="parent.email">{{ parent.email }}</span>
                        <span v-else class="text-slate-300">not signed up</span>
                    </UiTableCell>
                    <UiTableCell align="right">
                        {{ parent.children_count }}
                        <span v-if="parent.owned_children_count !== parent.children_count" class="text-slate-400">
                            ({{ parent.owned_children_count }} own)
                        </span>
                    </UiTableCell>
                    <UiTableCell align="right">{{ parent.current_streak }}</UiTableCell>
                    <UiTableCell align="right" cell-class="text-slate-500">
                        {{ parent.last_entry_date ?? '—' }}
                    </UiTableCell>
                    <UiTableCell align="right">
                        <UiButton variant="outline" :to="`/admin/users/${parent.id}`">Open</UiButton>
                    </UiTableCell>
                </UiTableRow>
            </template>

            <template #footer>
                <UiPagination
                    v-if="users.last_page > 1"
                    :current-page="users.current_page"
                    :last-page="users.last_page"
                    :total="users.total"
                    :per-page="users.per_page"
                    @change="(p: number) => visit({ page: p })"
                />
            </template>
        </UiTable>
    </AdminLayout>
</template>
