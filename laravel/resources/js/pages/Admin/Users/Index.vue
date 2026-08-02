<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import EyeIcon from '@heroicons/vue/24/outline/esm/EyeIcon.js';
import PlusIcon from '@heroicons/vue/24/outline/esm/PlusIcon.js';
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
import UiActionButton from '../../../components/ui/UiActionButton.vue';
import UiFilterPopover from '../../../components/ui/UiFilterPopover.vue';
import UiSelect from '../../../components/ui/UiSelect.vue';
import UiModal from '../../../components/ui/UiModal.vue';
import UiInput from '../../../components/ui/UiInput.vue';
import UiSwitch from '../../../components/ui/UiSwitch.vue';
import { useIndexFilters } from '../../../composables/useIndexFilters';
import { formatDate } from '../../../support/date';

interface Row {
    id: number;
    name: string;
    email: string | null;
    is_admin: boolean;
    current_streak: number;
    longest_streak: number;
    last_entry_date: string | null;
    created_at: string | null;
    deleted_at: string | null;
    owned_children_count: number;
    children_count: number;
    devices_count: number;
}

const props = defineProps<{
    users: { data: Row[]; current_page: number; last_page: number; total: number; per_page: number };
    filters: { search: string | null; sort: string | null; order: string | null; filter: string | null };
    counts: { all: number; admins: number; guests: number; deleted: number };
    timezone: string;
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

const formOpen = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    timezone: props.timezone,
    is_admin: false,
});

function startCreate() {
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function submit() {
    form.post('/admin/users', { onSuccess: () => (formOpen.value = false) });
}
</script>

<template>
    <Head title="Users" />

    <AdminLayout>
        <UiPageHeader title="Users" />

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
                <UiButton @click="startCreate">
                    <PlusIcon class="h-3.5 w-3.5" />
                    New user
                </UiButton>
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
                <UiSortableTableHeader
                    sort-key="created_at"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Joined
                </UiSortableTableHeader>
                <UiTableHeader align="right" />
            </template>

            <template #body>
                <UiTableRow v-for="user in users.data" :key="user.id">
                    <UiTableCell>
                        <div class="flex items-center gap-2">
                            <Link :href="`/admin/users/${user.id}`" class="font-medium text-slate-900 hover:underline">
                                {{ user.name }}
                            </Link>
                            <UiBadge v-if="user.is_admin" tone="primary">admin</UiBadge>
                            <UiBadge v-if="user.deleted_at" tone="danger">deleting</UiBadge>
                        </div>
                    </UiTableCell>
                    <UiTableCell cell-class="text-slate-500">
                        <span v-if="user.email">{{ user.email }}</span>
                        <span v-else class="text-slate-300">not signed up</span>
                    </UiTableCell>
                    <UiTableCell align="right">
                        {{ user.children_count }}
                        <span v-if="user.owned_children_count !== user.children_count" class="text-slate-400">
                            ({{ user.owned_children_count }} own)
                        </span>
                    </UiTableCell>
                    <UiTableCell align="right">{{ user.current_streak }}</UiTableCell>
                    <UiTableCell align="right" cell-class="text-slate-500">
                        {{ formatDate(user.last_entry_date) }}
                    </UiTableCell>
                    <UiTableCell align="right" cell-class="text-slate-500">
                        {{ formatDate(user.created_at) }}
                    </UiTableCell>
                    <UiTableCell align="right">
                        <div class="flex items-center justify-end">
                            <UiActionButton title="Open" size="sm" :to="`/admin/users/${user.id}`">
                                <EyeIcon class="h-4 w-4" />
                            </UiActionButton>
                        </div>
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

        <UiModal v-model="formOpen" title="New user">
            <form id="user-form" class="flex flex-col gap-4" @submit.prevent="submit">
                <UiInput v-model="form.name" label="Name" required :error="form.errors.name" />
                <UiInput v-model="form.email" type="email" label="Email" required :error="form.errors.email" />
                <UiInput
                    v-model="form.password"
                    type="password"
                    label="Password"
                    required
                    autocomplete="new-password"
                    :error="form.errors.password"
                />
                <UiInput
                    v-model="form.timezone"
                    label="Timezone"
                    required
                    hint="Decides when their day rolls over"
                    :error="form.errors.timezone"
                />
                <UiSwitch v-model="form.is_admin" label="Can reach this admin" />
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <UiButton variant="outline" @click="formOpen = false">Cancel</UiButton>
                    <UiButton type="submit" form="user-form" :loading="form.processing">Submit</UiButton>
                </div>
            </template>
        </UiModal>
    </AdminLayout>
</template>
