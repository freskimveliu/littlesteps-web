<script setup lang="ts">
import { computed } from 'vue';
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
    users: { data: Parent[]; current_page: number; last_page: number; total: number };
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

const tabs = [
    { key: null, label: 'Everyone', count: props.counts.all, hint: 'Every account, admins included' },
    { key: 'admins', label: 'Admins', count: props.counts.admins, hint: 'Can reach this console' },
    { key: 'guests', label: 'Not signed up', count: props.counts.guests, hint: 'Using the app with no email yet' },
    { key: 'deleted', label: 'Deleting', count: props.counts.deleted, hint: 'Inside the 30-day grace period' },
];

const active = computed(() => tabs.find((t) => t.key === (props.filters.filter ?? null)) ?? tabs[0]);

function pick(key: string | null) {
    router.get('/admin/users', { filter: key ?? undefined }, { preserveState: false });
}
</script>

<template>
    <Head title="Parents" />

    <AdminLayout>
        <UiPageHeader
            title="Parents"
            :subtitle="`${counts.all} accounts. A user exists from first launch, before anyone types an email.`"
        />

        <div class="mb-1 inline-flex rounded-ui border border-slate-200 bg-white p-1">
            <button
                v-for="tab in tabs"
                :key="tab.label"
                type="button"
                :disabled="tab.count === 0 && tab.key !== null"
                class="inline-flex items-center gap-2 rounded-ui px-3 py-1.5 text-body font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                :class="
                    (filters.filter ?? null) === tab.key
                        ? 'bg-primary text-primary-text'
                        : 'text-slate-600 hover:bg-slate-50'
                "
                @click="pick(tab.key)"
            >
                {{ tab.label }}
                <span
                    class="rounded-full px-1.5 text-label"
                    :class="(filters.filter ?? null) === tab.key ? 'bg-white/25' : 'bg-slate-100 text-slate-500'"
                >
                    {{ tab.count }}
                </span>
            </button>
        </div>
        <p class="mb-4 text-label text-slate-400">{{ active.hint }}</p>

        <UiTable :empty="users.data.length === 0" empty-title="No accounts match">
            <template #toolbar>
                <UiSpinner v-if="searching" size="xs" tone="primary" class="mr-2" />
                <UiSearchInput v-model="search" placeholder="Name or email…" />
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
                <div
                    v-if="users.last_page > 1"
                    class="flex items-center justify-between border-t border-[#f0f4f8] px-6 py-4 text-body text-slate-500"
                >
                    <span>Page {{ users.current_page }} of {{ users.last_page }}</span>
                    <div class="flex gap-2">
                        <UiButton variant="outline" :disabled="users.current_page === 1" @click="visit({ page: users.current_page - 1 })">
                            Previous
                        </UiButton>
                        <UiButton
                            variant="outline"
                            :disabled="users.current_page === users.last_page"
                            @click="visit({ page: users.current_page + 1 })"
                        >
                            Next
                        </UiButton>
                    </div>
                </div>
            </template>
        </UiTable>
    </AdminLayout>
</template>
