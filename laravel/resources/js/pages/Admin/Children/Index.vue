<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import PencilSquareIcon from '@heroicons/vue/24/outline/esm/PencilSquareIcon.js';
import TrashIcon from '@heroicons/vue/24/outline/esm/TrashIcon.js';
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
import UiAvatar from '../../../components/ui/UiAvatar.vue';
import UiPagination from '../../../components/ui/UiPagination.vue';
import UiActionButton from '../../../components/ui/UiActionButton.vue';
import UiModal from '../../../components/ui/UiModal.vue';
import UiInput from '../../../components/ui/UiInput.vue';
import UiSelect from '../../../components/ui/UiSelect.vue';
import UiConfirmationModal from '../../../components/ui/UiConfirmationModal.vue';
import { useIndexFilters } from '../../../composables/useIndexFilters';
import { formatDate } from '../../../support/date';

interface ChildRow {
    id: number;
    name: string;
    birthday: string;
    age_months: number;
    gender: string;
    xp: number;
    photo: string | null;
    level: number;
    level_name: string;
    entries_count: number;
    trophies_count: number;
    chapters_done_count: number;
    creator: { id: number; name: string; email: string | null } | null;
}

const props = defineProps<{
    children: { data: ChildRow[]; current_page: number; last_page: number; total: number; per_page: number };
    filters: { search: string | null; sort: string | null; order: string | null };
    genders: string[];
}>();

const { search, searching, sortKey, sortOrder, toggleSort, visit } = useIndexFilters({
    url: '/admin/children',
    search: props.filters.search,
    sortKey: props.filters.sort,
    sortOrder: props.filters.order as 'asc' | 'desc' | null,
});

const editing = ref<ChildRow | null>(null);
const formOpen = ref(false);
const toDelete = ref<ChildRow | null>(null);
const deleting = ref(false);

const form = useForm({ name: '', birthday: '', gender: '' });

function startEdit(child: ChildRow) {
    editing.value = child;
    form.defaults({ name: child.name, birthday: child.birthday, gender: child.gender });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function submit() {
    if (!editing.value) return;
    form.put(`/admin/children/${editing.value.id}`, {
        preserveScroll: true,
        onSuccess: () => (formOpen.value = false),
    });
}

function performDelete() {
    if (!toDelete.value) return;
    deleting.value = true;
    router.delete(`/admin/children/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            toDelete.value = null;
        },
    });
}

function age(months: number): string {
    if (months < 24) return `${months} mo`;
    return `${Math.floor(months / 12)} yr ${months % 12} mo`;
}

/** What the parent stands to lose, said plainly before the console erases it. */
function whatGoes(child: ChildRow): string {
    const counted = [
        [child.entries_count, 'memory', 'memories'],
        [child.trophies_count, 'trophy', 'trophies'],
    ] as const;

    const parts = counted
        .filter(([count]) => count > 0)
        .map(([count, one, many]) => `${count} ${count === 1 ? one : many}`);

    return parts.length
        ? `${child.name} and ${parts.join(' and ')} will be erased. This cannot be undone.`
        : `${child.name} and everything recorded for them will be erased. This cannot be undone.`;
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
                    sort-key="trophies_count"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Trophies
                </UiSortableTableHeader>
                <UiTableHeader align="right">Chapters</UiTableHeader>
                <UiTableHeader align="right" />
            </template>

            <template #body>
                <UiTableRow v-for="child in children.data" :key="child.id">
                    <UiTableCell>
                        <Link
                            :href="`/admin/children/${child.id}`"
                            class="group -mx-6 -my-3 flex items-center gap-3 px-6 py-3"
                        >
                            <UiAvatar :src="child.photo" :name="child.name" size="sm" />
                            <div class="min-w-0">
                                <span class="font-medium text-slate-900 group-hover:underline">
                                    {{ child.name }}
                                </span>
                                <p class="text-label text-slate-400">
                                    {{ child.gender }} · born {{ formatDate(child.birthday) }}
                                </p>
                            </div>
                        </Link>
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
                    <UiTableCell align="right">{{ child.trophies_count }}</UiTableCell>
                    <UiTableCell align="right">{{ child.chapters_done_count }} / 8</UiTableCell>
                    <UiTableCell align="right">
                        <div class="flex items-center justify-end gap-2">
                            <UiButton variant="outline" :to="`/admin/children/${child.id}`">Open</UiButton>
                            <UiActionButton title="Edit" size="sm" @click="startEdit(child)">
                                <PencilSquareIcon class="h-4 w-4" />
                            </UiActionButton>
                            <UiActionButton title="Delete" size="sm" @click="toDelete = child">
                                <TrashIcon class="h-4 w-4" />
                            </UiActionButton>
                        </div>
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

        <UiModal v-model="formOpen" :title="editing ? `Edit ${editing.name}` : 'Edit child'">
            <form id="child-form" class="flex flex-col gap-4" @submit.prevent="submit">
                <UiInput v-model="form.name" label="Name" required :error="form.errors.name" />
                <UiInput
                    v-model="form.birthday"
                    type="date"
                    label="Birthday"
                    required
                    :error="form.errors.birthday"
                />
                <UiSelect
                    v-model="form.gender"
                    label="Gender"
                    required
                    :error="form.errors.gender"
                    :options="genders.map((g) => ({ value: g, label: g.replaceAll('-', ' ') }))"
                />
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <UiButton variant="outline" @click="formOpen = false">Cancel</UiButton>
                    <UiButton type="submit" form="child-form" :loading="form.processing">Save</UiButton>
                </div>
            </template>
        </UiModal>

        <UiConfirmationModal
            :model-value="!!toDelete"
            title="Delete this child?"
            :message="toDelete ? whatGoes(toDelete) : ''"
            confirm-text="Delete"
            confirm-variant="danger"
            :loading="deleting"
            @update:model-value="(v: boolean) => { if (!v) toDelete = null }"
            @confirm="performDelete"
        />
    </AdminLayout>
</template>
