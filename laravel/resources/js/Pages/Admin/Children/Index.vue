<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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
import { useIndexFilters } from '../../../composables/useIndexFilters';

interface ChildRow {
    id: number;
    name: string;
    birthday: string;
    age_months: number;
    gender: string;
    xp: number;
    level: number;
    level_name: string;
    entries_count: number;
    achievements_count: number;
    chapters_done_count: number;
    creator: { id: number; name: string; email: string | null } | null;
}

const props = defineProps<{
    children: { data: ChildRow[]; current_page: number; last_page: number; total: number; per_page: number };
    filters: { search: string | null; sort: string | null; order: string | null };
}>();

const { search, searching, sortKey, sortOrder, toggleSort, visit } = useIndexFilters({
    url: '/admin/children',
    search: props.filters.search,
    sortKey: props.filters.sort,
    sortOrder: props.filters.order as 'asc' | 'desc' | null,
});

function age(months: number): string {
    if (months < 24) return `${months} mo`;
    return `${Math.floor(months / 12)} yr ${months % 12} mo`;
}
</script>

<template>
    <Head title="Children" />

    <AdminLayout>
        <UiPageHeader title="Children" />

        <UiTable :empty="children.data.length === 0" empty-title="No children match">
            <template #toolbar>
                <UiSpinner v-if="searching" size="xs" tone="primary" class="mr-2" />
                <UiSearchInput v-model="search" placeholder="Search by name…" />
            </template>

            <template #header>
                <UiSortableTableHeader sort-key="name" :active-key="sortKey" :active-order="sortOrder" @sort="toggleSort">
                    Name
                </UiSortableTableHeader>
                <UiTableHeader>Parent</UiTableHeader>
                <UiSortableTableHeader
                    sort-key="birthday"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Age
                </UiSortableTableHeader>
                <UiSortableTableHeader
                    sort-key="xp"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Level
                </UiSortableTableHeader>
                <UiSortableTableHeader
                    sort-key="entries_count"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Memories
                </UiSortableTableHeader>
                <UiSortableTableHeader
                    sort-key="achievements_count"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Badges
                </UiSortableTableHeader>
                <UiTableHeader align="right">Chapters</UiTableHeader>
                <UiTableHeader align="right" />
            </template>

            <template #body>
                <UiTableRow v-for="child in children.data" :key="child.id">
                    <UiTableCell>
                        <Link :href="`/admin/children/${child.id}`" class="font-medium text-slate-900 hover:underline">
                            {{ child.name }}
                        </Link>
                        <p class="text-label text-slate-400">{{ child.gender }} · born {{ child.birthday }}</p>
                    </UiTableCell>
                    <UiTableCell>
                        <Link
                            v-if="child.creator"
                            :href="`/admin/users/${child.creator.id}`"
                            class="text-slate-600 hover:underline"
                        >
                            {{ child.creator.name }}
                        </Link>
                    </UiTableCell>
                    <UiTableCell align="right" cell-class="text-slate-500">{{ age(child.age_months) }}</UiTableCell>
                    <UiTableCell align="right">
                        <UiBadge tone="primary">{{ child.level }}</UiBadge>
                        <span class="ml-1 text-slate-400">{{ child.xp }} XP</span>
                    </UiTableCell>
                    <UiTableCell align="right">{{ child.entries_count }}</UiTableCell>
                    <UiTableCell align="right">{{ child.achievements_count }}</UiTableCell>
                    <UiTableCell align="right">{{ child.chapters_done_count }} / 8</UiTableCell>
                    <UiTableCell align="right">
                        <UiButton variant="outline" :to="`/admin/children/${child.id}`">Open</UiButton>
                    </UiTableCell>
                </UiTableRow>
            </template>

            <template #footer>
                <UiPagination
                    v-if="children.last_page > 1"
                    :current-page="children.current_page"
                    :last-page="children.last_page"
                    :total="children.total"
                    :per-page="children.per_page"
                    @change="(p: number) => visit({ page: p })"
                />
            </template>
        </UiTable>
    </AdminLayout>
</template>
