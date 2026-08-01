<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import PlusIcon from '@heroicons/vue/24/outline/esm/PlusIcon.js';
import PencilSquareIcon from '@heroicons/vue/24/outline/esm/PencilSquareIcon.js';
import TrashIcon from '@heroicons/vue/24/outline/esm/TrashIcon.js';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import UiPageHeader from '../../../components/ui/UiPageHeader.vue';
import UiTable from '../../../components/ui/UiTable.vue';
import UiTableRow from '../../../components/ui/UiTableRow.vue';
import UiTableCell from '../../../components/ui/UiTableCell.vue';
import UiTableHeader from '../../../components/ui/UiTableHeader.vue';
import UiSortableTableHeader from '../../../components/ui/UiSortableTableHeader.vue';
import UiActionButton from '../../../components/ui/UiActionButton.vue';
import UiSearchInput from '../../../components/ui/UiSearchInput.vue';
import UiButton from '../../../components/ui/UiButton.vue';
import UiSpinner from '../../../components/ui/UiSpinner.vue';
import UiBadge from '../../../components/ui/UiBadge.vue';
import UiModal from '../../../components/ui/UiModal.vue';
import UiInput from '../../../components/ui/UiInput.vue';
import UiSelect from '../../../components/ui/UiSelect.vue';
import UiSwitch from '../../../components/ui/UiSwitch.vue';
import UiConfirmationModal from '../../../components/ui/UiConfirmationModal.vue';
import { useIndexFilters } from '../../../composables/useIndexFilters';

interface Milestone {
    id: number;
    name: string;
    description: string | null;
    icon: string;
    months_from: number | null;
    xp: number;
    sort_order: number;
    is_editable: boolean;
    is_active: boolean;
    steps_count: number;
}

const props = defineProps<{
    milestones: Milestone[];
    filters: { search: string | null; sort: string | null; order: string | null };
    icons: string[];
}>();

const { search, searching, sortKey, sortOrder, toggleSort } = useIndexFilters({
    url: '/admin/milestones',
    search: props.filters.search,
    sortKey: props.filters.sort,
    sortOrder: props.filters.order as 'asc' | 'desc' | null,
});

const formOpen = ref(false);
const editing = ref<Milestone | null>(null);
const toDelete = ref<Milestone | null>(null);

const form = useForm({
    name: '',
    description: '',
    icon: 'star',
    months_from: 0,
    xp: 150,
    sort_order: 0,
    is_editable: false,
    is_active: true,
});

function startCreate() {
    editing.value = null;
    form.defaults({
        name: '',
        description: '',
        icon: 'star',
        months_from: 0,
        xp: 150,
        sort_order: (props.milestones.at(-1)?.sort_order ?? 0) + 10,
        is_editable: false,
        is_active: true,
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function startEdit(milestone: Milestone) {
    editing.value = milestone;
    form.defaults({
        name: milestone.name,
        description: milestone.description ?? '',
        icon: milestone.icon,
        months_from: milestone.months_from ?? 0,
        xp: milestone.xp,
        sort_order: milestone.sort_order,
        is_editable: milestone.is_editable,
        is_active: milestone.is_active,
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function submit() {
    const done = { onSuccess: () => (formOpen.value = false) };
    editing.value ? form.put(`/admin/milestones/${editing.value.id}`, done) : form.post('/admin/milestones', done);
}

function performDelete() {
    if (!toDelete.value) return;
    router.delete(`/admin/milestones/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => (toDelete.value = null),
    });
}
</script>

<template>
    <Head title="Milestones" />

    <AdminLayout>
        <UiPageHeader
            title="Milestones"
            subtitle="The eight parts of the journey. A milestone opens at its months_from and is finished by the parent."
        />

        <UiTable :empty="milestones.length === 0" empty-title="No milestones yet">
            <template #toolbar>
                <UiSpinner v-if="searching" size="xs" tone="primary" class="mr-2" />
                <UiSearchInput v-model="search" placeholder="Search milestones…" />
                <UiButton @click="startCreate">
                    <PlusIcon class="h-3.5 w-3.5" />
                    New milestone
                </UiButton>
            </template>

            <template #header>
                <UiSortableTableHeader sort-key="name" :active-key="sortKey" :active-order="sortOrder" @sort="toggleSort">
                    Name
                </UiSortableTableHeader>
                <UiSortableTableHeader
                    sort-key="months_from"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Opens at
                </UiSortableTableHeader>
                <UiSortableTableHeader
                    sort-key="steps_count"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Steps
                </UiSortableTableHeader>
                <UiSortableTableHeader
                    sort-key="xp"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    XP
                </UiSortableTableHeader>
                <UiTableHeader align="right">Actions</UiTableHeader>
            </template>

            <template #body>
                <UiTableRow v-for="milestone in milestones" :key="milestone.id">
                    <UiTableCell>
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-slate-900">{{ milestone.name }}</span>
                            <UiBadge v-if="!milestone.is_active" tone="danger">inactive</UiBadge>
                        </div>
                        <p v-if="milestone.description" class="text-label text-slate-400">{{ milestone.description }}</p>
                    </UiTableCell>
                    <UiTableCell align="right">{{ milestone.months_from }} mo</UiTableCell>
                    <UiTableCell align="right">
                        <Link
                            :href="`/admin/steps?milestone=${milestone.id}`"
                            class="text-primary-accessible hover:underline"
                        >
                            {{ milestone.steps_count }}
                        </Link>
                    </UiTableCell>
                    <UiTableCell align="right">{{ milestone.xp }}</UiTableCell>
                    <UiTableCell align="right">
                        <div class="flex items-center justify-end gap-2">
                            <UiActionButton title="Edit" size="sm" @click="startEdit(milestone)">
                                <PencilSquareIcon class="h-4 w-4" />
                            </UiActionButton>
                            <UiActionButton title="Delete" size="sm" @click="toDelete = milestone">
                                <TrashIcon class="h-4 w-4" />
                            </UiActionButton>
                        </div>
                    </UiTableCell>
                </UiTableRow>
            </template>
        </UiTable>

        <UiModal v-model="formOpen" :title="editing ? 'Edit milestone' : 'New milestone'">
            <form id="milestone-form" class="flex flex-col gap-4" @submit.prevent="submit">
                <UiInput v-model="form.name" label="Name" required :error="form.errors.name" />
                <UiInput v-model="form.description" label="Description" :error="form.errors.description" />
                <UiSelect
                    v-model="form.icon"
                    label="Icon"
                    required
                    :error="form.errors.icon"
                    :options="icons.map((i) => ({ value: i, label: i }))"
                />
                <div class="grid grid-cols-3 gap-3">
                    <UiInput
                        v-model="form.months_from"
                        type="number"
                        label="Opens at (months)"
                        :error="form.errors.months_from"
                    />
                    <UiInput
                        v-model="form.xp"
                        type="number"
                        label="XP for finishing"
                        required
                        :error="form.errors.xp"
                    />
                    <UiInput
                        v-model="form.sort_order"
                        type="number"
                        label="Order"
                        required
                        :error="form.errors.sort_order"
                    />
                </div>
                <div class="flex gap-6">
                    <UiSwitch v-model="form.is_editable" label="Parents may rename it" />
                    <UiSwitch v-model="form.is_active" label="Active" />
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <UiButton variant="outline" @click="formOpen = false">Cancel</UiButton>
                    <UiButton type="submit" form="milestone-form" :loading="form.processing">Submit</UiButton>
                </div>
            </template>
        </UiModal>

        <UiConfirmationModal
            :model-value="!!toDelete"
            title="Remove milestone?"
            :message="
                toDelete
                    ? `“${toDelete.name}” leaves the catalogue. Children already provisioned keep their own copy.`
                    : ''
            "
            confirm-text="Remove"
            confirm-variant="danger"
            @update:model-value="(v: boolean) => { if (!v) toDelete = null }"
            @confirm="performDelete"
        />
    </AdminLayout>
</template>
