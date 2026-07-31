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
import { useIndexFilters } from '../../../composables/useIndexFilters';

interface Property {
    id?: number;
    key: string;
    name: string | null;
}

interface Step {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    icon: string | null;
    months_from: number | null;
    xp: number;
    sort_order: number;
    is_active: boolean;
    template_milestone_id: number;
    category_id: number;
    milestone: { id: number; name: string } | null;
    category: { id: number; name: string; color: string } | null;
    properties: Property[];
}

const props = defineProps<{
    steps: { data: Step[]; current_page: number; last_page: number; total: number; links: unknown[] };
    filters: { search: string | null; sort: string | null; order: string | null; chapter: number | null };
    chapters: { id: number; name: string }[];
    categories: { id: number; name: string; color: string }[];
    icons: string[];
    propertyKeys: string[];
}>();

const chapterFilter = ref<string>(props.filters.chapter ? String(props.filters.chapter) : '');

const { search, searching, sortKey, sortOrder, toggleSort, visit } = useIndexFilters({
    url: '/admin/steps',
    search: props.filters.search,
    sortKey: props.filters.sort,
    sortOrder: props.filters.order as 'asc' | 'desc' | null,
});

function applyChapter() {
    visit({ chapter: chapterFilter.value || undefined });
}

const formOpen = ref(false);
const editing = ref<Step | null>(null);
const toDelete = ref<Step | null>(null);

const form = useForm({
    slug: '',
    template_milestone_id: props.chapters[0]?.id ?? 0,
    category_id: props.categories[0]?.id ?? 0,
    name: '',
    description: '',
    icon: '',
    months_from: 0,
    xp: 25,
    sort_order: 0,
    is_active: true,
    properties: [] as Property[],
});

function startCreate() {
    editing.value = null;
    form.defaults({
        slug: '',
        template_milestone_id: Number(chapterFilter.value) || props.chapters[0]?.id || 0,
        category_id: props.categories[0]?.id ?? 0,
        name: '',
        description: '',
        icon: '',
        months_from: 0,
        xp: 25,
        sort_order: 0,
        is_active: true,
        properties: [],
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function startEdit(step: Step) {
    editing.value = step;
    form.defaults({
        slug: step.slug,
        template_milestone_id: step.template_milestone_id,
        category_id: step.category_id,
        name: step.name,
        description: step.description ?? '',
        icon: step.icon ?? '',
        months_from: step.months_from ?? 0,
        xp: step.xp,
        sort_order: step.sort_order,
        is_active: step.is_active,
        properties: step.properties.map((p) => ({ key: p.key, name: p.name })),
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
    editing.value ? form.put(`/admin/steps/${editing.value.id}`, done) : form.post('/admin/steps', done);
}

function performDelete() {
    if (!toDelete.value) return;
    router.delete(`/admin/steps/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => (toDelete.value = null),
    });
}
</script>

<template>
    <Head title="Steps" />

    <AdminLayout>
        <UiPageHeader
            title="Steps"
            :subtitle="`${steps.total} questions across the journey. Editing one never rewrites what a parent already saved.`"
        />

        <UiTable :empty="steps.data.length === 0" empty-title="No steps match">
            <template #toolbar>
                <UiSpinner v-if="searching" size="xs" tone="primary" class="mr-2" />
                <div class="w-52">
                    <UiSelect
                        v-model="chapterFilter"
                        placeholder="All chapters"
                        :options="[
                            { value: '', label: 'All chapters' },
                            ...chapters.map((c) => ({ value: String(c.id), label: c.name })),
                        ]"
                        @update:model-value="applyChapter"
                    />
                </div>
                <UiSearchInput v-model="search" placeholder="Search steps…" />
                <UiButton @click="startCreate">
                    <PlusIcon class="h-3.5 w-3.5" />
                    New step
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
                <UiTableRow v-for="step in steps.data" :key="step.id">
                    <UiTableCell>
                        <span class="font-medium text-slate-900">{{ step.name }}</span>
                        <p class="text-label text-slate-400">{{ step.slug }}</p>
                    </UiTableCell>
                    <UiTableCell cell-class="text-slate-500">{{ step.milestone?.name }}</UiTableCell>
                    <UiTableCell>
                        <div v-if="step.category" class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: step.category.color }" />
                            <span class="text-slate-600">{{ step.category.name }}</span>
                        </div>
                    </UiTableCell>
                    <UiTableCell>
                        <div class="flex flex-wrap gap-1">
                            <UiBadge v-for="(p, i) in step.properties" :key="i" :tone="p.key === 'custom' ? 'neutral' : 'primary'">
                                {{ p.name ?? p.key }}
                            </UiBadge>
                        </div>
                    </UiTableCell>
                    <UiTableCell align="right">{{ step.months_from }} mo</UiTableCell>
                    <UiTableCell align="right">{{ step.xp }}</UiTableCell>
                    <UiTableCell align="right">
                        <div class="flex items-center justify-end gap-2">
                            <UiActionButton title="Edit" size="sm" @click="startEdit(step)">
                                <PencilSquareIcon class="h-4 w-4" />
                            </UiActionButton>
                            <UiActionButton title="Delete" size="sm" @click="toDelete = step">
                                <TrashIcon class="h-4 w-4" />
                            </UiActionButton>
                        </div>
                    </UiTableCell>
                </UiTableRow>
            </template>

            <template #footer>
                <div
                    v-if="steps.last_page > 1"
                    class="flex items-center justify-between border-t border-[#f0f4f8] px-6 py-4 text-body text-slate-500"
                >
                    <span>Page {{ steps.current_page }} of {{ steps.last_page }}</span>
                    <div class="flex gap-2">
                        <UiButton
                            variant="outline"
                            :disabled="steps.current_page === 1"
                            @click="visit({ page: steps.current_page - 1, chapter: chapterFilter || undefined })"
                        >
                            Previous
                        </UiButton>
                        <UiButton
                            variant="outline"
                            :disabled="steps.current_page === steps.last_page"
                            @click="visit({ page: steps.current_page + 1, chapter: chapterFilter || undefined })"
                        >
                            Next
                        </UiButton>
                    </div>
                </div>
            </template>
        </UiTable>

        <UiModal v-model="formOpen" size="lg" :title="editing ? 'Edit step' : 'New step'">
            <form id="step-form" class="flex flex-col gap-4" @submit.prevent="submit">
                <div class="grid grid-cols-2 gap-3">
                    <UiInput v-model="form.name" label="Name" required :error="form.errors.name" />
                    <UiInput v-model="form.slug" label="Slug" required :error="form.errors.slug" />
                </div>

                <UiInput v-model="form.description" label="Description" :error="form.errors.description" />

                <div class="grid grid-cols-2 gap-3">
                    <UiSelect
                        v-model="form.template_milestone_id"
                        label="Chapter"
                        required
                        :error="form.errors.template_milestone_id"
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

                <UiSwitch v-model="form.is_active" label="Active" />
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <UiButton variant="outline" @click="formOpen = false">Cancel</UiButton>
                    <UiButton type="submit" form="step-form" :loading="form.processing">Submit</UiButton>
                </div>
            </template>
        </UiModal>

        <UiConfirmationModal
            :model-value="!!toDelete"
            title="Remove step?"
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
