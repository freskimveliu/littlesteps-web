<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
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
import UiButton from '../../../components/ui/UiButton.vue';
import UiBadge from '../../../components/ui/UiBadge.vue';
import UiModal from '../../../components/ui/UiModal.vue';
import UiInput from '../../../components/ui/UiInput.vue';
import UiSelect from '../../../components/ui/UiSelect.vue';
import UiSwitch from '../../../components/ui/UiSwitch.vue';
import UiConfirmationModal from '../../../components/ui/UiConfirmationModal.vue';
import { useIndexFilters } from '../../../composables/useIndexFilters';

interface Level {
    id: number;
    name: string;
    icon: string;
    min_xp: number;
    is_active: boolean;
}

const props = defineProps<{
    levels: Level[];
    filters: { search: string | null; sort: string | null; order: string | null };
    icons: string[];
}>();

const { sortKey, sortOrder, toggleSort } = useIndexFilters({
    url: '/admin/levels',
    sortKey: props.filters.sort,
    sortOrder: props.filters.order as 'asc' | 'desc' | null,
});

const formOpen = ref(false);
const editing = ref<Level | null>(null);
const toDelete = ref<Level | null>(null);

const form = useForm({ name: '', icon: 'star', min_xp: 0, is_active: true });

function startCreate() {
    editing.value = null;
    form.defaults({ name: '', icon: 'star', min_xp: (props.levels.at(-1)?.min_xp ?? 0) + 1000, is_active: true });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function startEdit(level: Level) {
    editing.value = level;
    form.defaults({ name: level.name, icon: level.icon, min_xp: level.min_xp, is_active: level.is_active });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function submit() {
    const done = { onSuccess: () => (formOpen.value = false) };
    editing.value ? form.put(`/admin/levels/${editing.value.id}`, done) : form.post('/admin/levels', done);
}

function performDelete() {
    if (!toDelete.value) return;
    router.delete(`/admin/levels/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => (toDelete.value = null),
    });
}
</script>

<template>
    <Head title="Levels" />

    <AdminLayout>
        <UiPageHeader
            title="Levels"
            subtitle="The ladder shown as the Level Journey. A level is its position in this list, ordered by XP."
        />

        <UiTable :empty="levels.length === 0" empty-title="No levels yet">
            <template #toolbar>
                <UiButton @click="startCreate">
                    <PlusIcon class="h-3.5 w-3.5" />
                    New level
                </UiButton>
            </template>

            <template #header>
                <UiTableHeader cell-class="w-16">#</UiTableHeader>
                <UiSortableTableHeader sort-key="name" :active-key="sortKey" :active-order="sortOrder" @sort="toggleSort">
                    Name
                </UiSortableTableHeader>
                <UiTableHeader>Icon</UiTableHeader>
                <UiSortableTableHeader
                    sort-key="min_xp"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Reached at
                </UiSortableTableHeader>
                <UiTableHeader align="right">Actions</UiTableHeader>
            </template>

            <template #body>
                <UiTableRow v-for="(level, i) in levels" :key="level.id">
                    <UiTableCell cell-class="text-slate-400">{{ i + 1 }}</UiTableCell>
                    <UiTableCell>
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-slate-900">{{ level.name }}</span>
                            <UiBadge v-if="!level.is_active" tone="danger">inactive</UiBadge>
                        </div>
                    </UiTableCell>
                    <UiTableCell cell-class="text-slate-500">{{ level.icon }}</UiTableCell>
                    <UiTableCell align="right">{{ level.min_xp.toLocaleString() }} XP</UiTableCell>
                    <UiTableCell align="right">
                        <div class="flex items-center justify-end gap-2">
                            <UiActionButton title="Edit" size="sm" @click="startEdit(level)">
                                <PencilSquareIcon class="h-4 w-4" />
                            </UiActionButton>
                            <UiActionButton
                                title="Delete"
                                size="sm"
                                :disabled="level.min_xp === 0"
                                @click="toDelete = level"
                            >
                                <TrashIcon class="h-4 w-4" />
                            </UiActionButton>
                        </div>
                    </UiTableCell>
                </UiTableRow>
            </template>
        </UiTable>

        <UiModal v-model="formOpen" :title="editing ? 'Edit level' : 'New level'">
            <form id="level-form" class="flex flex-col gap-4" @submit.prevent="submit">
                <UiInput v-model="form.name" label="Name" required :error="form.errors.name" />
                <UiSelect
                    v-model="form.icon"
                    label="Icon"
                    required
                    :error="form.errors.icon"
                    :options="icons.map((i) => ({ value: i, label: i }))"
                />
                <UiInput
                    v-model="form.min_xp"
                    type="number"
                    label="Reached at (XP)"
                    required
                    hint="Two levels cannot share a rung"
                    :error="form.errors.min_xp"
                />
                <UiSwitch v-model="form.is_active" label="Active" />
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <UiButton variant="outline" @click="formOpen = false">Cancel</UiButton>
                    <UiButton type="submit" form="level-form" :loading="form.processing">Submit</UiButton>
                </div>
            </template>
        </UiModal>

        <UiConfirmationModal
            :model-value="!!toDelete"
            title="Remove level?"
            :message="toDelete ? `“${toDelete.name}” leaves the ladder. Children keep the XP they have.` : ''"
            confirm-text="Remove"
            confirm-variant="danger"
            @update:model-value="(v: boolean) => { if (!v) toDelete = null }"
            @confirm="performDelete"
        />
    </AdminLayout>
</template>
