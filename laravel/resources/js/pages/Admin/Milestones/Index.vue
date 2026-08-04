<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import PlusIcon from '@heroicons/vue/24/outline/esm/PlusIcon.js';
import PencilSquareIcon from '@heroicons/vue/24/outline/esm/PencilSquareIcon.js';
import TrashIcon from '@heroicons/vue/24/outline/esm/TrashIcon.js';
import XMarkIcon from '@heroicons/vue/24/outline/esm/XMarkIcon.js';
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
import UiPagination from '../../../components/ui/UiPagination.vue';
import UiFilterPopover from '../../../components/ui/UiFilterPopover.vue';
import { useIndexFilters } from '../../../composables/useIndexFilters';

interface Property {
    id?: number;
    key: string;
    name: string | null;
}

interface Milestone {
    id: number;
    name: string;
    description: string | null;
    icon: string | null;
    months_from: number | null;
    is_dated: boolean;
    xp: number;
    sort_order: number;
    is_active: boolean;
    chapter_id: number;
    category_id: number;
    chapter: { id: number; name: string } | null;
    category: { id: number; name: string; color: string } | null;
    properties: Property[];
}

const props = defineProps<{
    milestones: { data: Milestone[]; current_page: number; last_page: number; total: number; per_page: number; links: unknown[] };
    filters: { search: string | null; sort: string | null; order: string | null; chapter: number | null };
    chapters: { id: number; name: string }[];
    categories: { id: number; name: string; color: string }[];
    icons: string[];
    propertyKeys: string[];
}>();

const chapterFilter = ref<string>(props.filters.chapter ? String(props.filters.chapter) : '');

const { search, searching, sortKey, sortOrder, toggleSort, visit } = useIndexFilters({
    url: '/admin/milestones',
    search: props.filters.search,
    sortKey: props.filters.sort,
    sortOrder: props.filters.order as 'asc' | 'desc' | null,
});

function applyChapter() {
    visit({ chapter: chapterFilter.value || undefined });
}

function resetChapter() {
    chapterFilter.value = '';
    visit({ chapter: undefined });
}

const formOpen = ref(false);
const editing = ref<Milestone | null>(null);
const toDelete = ref<Milestone | null>(null);

const form = useForm({
    chapter_id: props.chapters[0]?.id ?? 0,
    category_id: props.categories[0]?.id ?? 0,
    name: '',
    description: '',
    icon: '',
    months_from: 0,
    is_dated: false,
    xp: 25,
    sort_order: 0,
    is_active: true,
    properties: [] as Property[],
});

function startCreate() {
    editing.value = null;
    form.defaults({
        chapter_id: Number(chapterFilter.value) || props.chapters[0]?.id || 0,
        category_id: props.categories[0]?.id ?? 0,
        name: '',
        description: '',
        icon: '',
        months_from: 0,
        is_dated: false,
        xp: 25,
        sort_order: 0,
        is_active: true,
        properties: [],
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function startEdit(milestone: Milestone) {
    editing.value = milestone;
    form.defaults({
        chapter_id: milestone.chapter_id,
        category_id: milestone.category_id,
        name: milestone.name,
        description: milestone.description ?? '',
        icon: milestone.icon ?? '',
        months_from: milestone.months_from ?? 0,
        is_dated: milestone.is_dated,
        xp: milestone.xp,
        sort_order: milestone.sort_order,
        is_active: milestone.is_active,
        properties: milestone.properties.map((p) => ({ key: p.key, name: p.name })),
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function addProperty() {
    form.properties.push({ key: 'weight', name: null });
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
        <UiPageHeader title="Milestones" />

        <UiTable :empty="milestones.data.length === 0" empty-title="No milestones match">
            <template #toolbar>
                <UiSpinner v-if="searching" size="xs" tone="primary" class="mr-2" />
                <UiSearchInput v-model="search" placeholder="Search milestones…" />
                <UiFilterPopover
                    :active-count="chapterFilter ? 1 : 0"
                    title="Filters"
                    @apply="applyChapter"
                    @reset="resetChapter"
                >
                    <UiSelect
                        v-model="chapterFilter"
                        label="Chapter"
                        :options="[
                            { value: '', label: 'All chapters' },
                            ...chapters.map((c) => ({ value: String(c.id), label: c.name })),
                        ]"
                    />
                </UiFilterPopover>
                <UiButton @click="startCreate">
                    <PlusIcon class="h-3.5 w-3.5" />
                    New milestone
                </UiButton>
            </template>

            <template #header>
                <UiSortableTableHeader sort-key="name" :active-key="sortKey" :active-order="sortOrder" @sort="toggleSort">
                    Name
                </UiSortableTableHeader>
                <UiTableHeader>Chapter</UiTableHeader>
                <UiTableHeader>Category</UiTableHeader>
                <UiTableHeader>Asks for</UiTableHeader>
                <UiSortableTableHeader
                    sort-key="months_from"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Unlocks
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
                <UiTableRow v-for="milestone in milestones.data" :key="milestone.id">
                    <UiTableCell>
                        <span class="font-medium text-slate-900">{{ milestone.name }}</span>
                    </UiTableCell>
                    <UiTableCell cell-class="text-slate-500">{{ milestone.chapter?.name }}</UiTableCell>
                    <UiTableCell>
                        <div v-if="milestone.category" class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: milestone.category.color }" />
                            <span class="text-slate-600">{{ milestone.category.name }}</span>
                        </div>
                    </UiTableCell>
                    <UiTableCell>
                        <div class="flex flex-wrap gap-1">
                            <UiBadge v-for="(p, i) in milestone.properties" :key="i" :tone="p.key === 'custom' ? 'neutral' : 'primary'">
                                {{ p.name ?? p.key }}
                            </UiBadge>
                        </div>
                    </UiTableCell>
                    <UiTableCell align="right">{{ milestone.months_from }} mo</UiTableCell>
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

            <template #footer>
                <UiPagination
                    v-if="milestones.last_page > 1"
                    :current-page="milestones.current_page"
                    :last-page="milestones.last_page"
                    :total="milestones.total"
                    :per-page="milestones.per_page"
                    @change="(p: number) => visit({ page: p, chapter: chapterFilter || undefined })"
                />
            </template>
        </UiTable>

        <UiModal v-model="formOpen" size="lg" :title="editing ? 'Edit milestone' : 'New milestone'">
            <form id="milestone-form" class="flex flex-col gap-4" @submit.prevent="submit">
                <div class="grid grid-cols-2 gap-3">
                    <UiInput v-model="form.name" label="Name" required :error="form.errors.name" />
                </div>

                <UiInput v-model="form.description" label="Description" :error="form.errors.description" />

                <div class="grid grid-cols-2 gap-3">
                    <UiSelect
                        v-model="form.chapter_id"
                        label="Chapter"
                        required
                        :error="form.errors.chapter_id"
                        :options="chapters.map((c) => ({ value: c.id, label: c.name }))"
                    />
                    <UiSelect
                        v-model="form.category_id"
                        label="Category"
                        required
                        :error="form.errors.category_id"
                        :options="categories.map((c) => ({ value: c.id, label: c.name }))"
                    />
                </div>

                <UiSelect
                    v-model="form.icon"
                    label="Icon"
                    hint="Leave empty to fall back to the category's icon"
                    :error="form.errors.icon"
                    :options="[{ value: '', label: '— use the category icon —' }, ...icons.map((i) => ({ value: i, label: i }))]"
                />

                <div class="grid grid-cols-3 gap-3">
                    <UiInput
                        v-model="form.months_from"
                        type="number"
                        label="Unlocks at (months)"
                        :error="form.errors.months_from"
                    />
                    <UiInput v-model="form.xp" type="number" label="XP" required :error="form.errors.xp" />
                    <UiInput
                        v-model="form.sort_order"
                        type="number"
                        label="Order"
                        required
                        :error="form.errors.sort_order"
                    />
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-label font-semibold text-slate-900">What it asks for</p>
                        <UiButton variant="outline" size="md" @click="addProperty">
                            <PlusIcon class="h-3 w-3" />
                            Add
                        </UiButton>
                    </div>

                    <p v-if="form.properties.length === 0" class="text-body text-slate-400">
                        Nothing measured — just the story and the day.
                    </p>

                    <div v-for="(property, i) in form.properties" :key="i" class="mb-2 flex items-end gap-2">
                        <div class="w-40">
                            <UiSelect
                                v-model="property.key"
                                :options="propertyKeys.map((k) => ({ value: k, label: k }))"
                            />
                        </div>
                        <div class="flex-1">
                            <UiInput
                                v-model="property.name"
                                :placeholder="property.key === 'custom' ? 'Label, e.g. Shoe size' : 'Uses the built-in label'"
                                :disabled="property.key !== 'custom'"
                                :error="(form.errors as Record<string, string>)[`properties.${i}.name`]"
                            />
                        </div>
                        <UiActionButton title="Remove" variant="filled" tone="danger" @click="form.properties.splice(i, 1)">
                            <XMarkIcon class="h-4 w-4" />
                        </UiActionButton>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <UiSwitch v-model="form.is_dated" label="This milestone is a date" />
                    <p class="text-caption text-slate-500">
                        On for “Month 5” or “Fourth Birthday” — a fixed point, so a parent cannot move it to
                        another chapter or change its place in the order. Off for a first, which happens
                        whenever it happens.
                    </p>
                </div>

                <UiSwitch v-model="form.is_active" label="Active" />
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
