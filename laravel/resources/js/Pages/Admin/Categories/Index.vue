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
import UiSearchInput from '../../../components/ui/UiSearchInput.vue';
import UiButton from '../../../components/ui/UiButton.vue';
import UiSpinner from '../../../components/ui/UiSpinner.vue';
import UiModal from '../../../components/ui/UiModal.vue';
import UiInput from '../../../components/ui/UiInput.vue';
import UiSelect from '../../../components/ui/UiSelect.vue';
import UiSwitch from '../../../components/ui/UiSwitch.vue';
import UiConfirmationModal from '../../../components/ui/UiConfirmationModal.vue';
import { useIndexFilters } from '../../../composables/useIndexFilters';

interface Category {
    id: number;
    name: string;
    description: string | null;
    icon: string;
    color: string;
    sort_order: number;
    is_active: boolean;
    template_steps_count: number;
}

const props = defineProps<{
    categories: Category[];
    filters: { search: string | null; sort: string | null; order: string | null };
    icons: string[];
}>();

const { search, searching, sortKey, sortOrder, toggleSort } = useIndexFilters({
    url: '/admin/categories',
    search: props.filters.search,
    sortKey: props.filters.sort,
    sortOrder: props.filters.order as 'asc' | 'desc' | null,
});

const formOpen = ref(false);
const editing = ref<Category | null>(null);
const toDelete = ref<Category | null>(null);

const form = useForm({
    name: '',
    description: '',
    icon: 'sparkles',
    color: '#7E5EBF',
    sort_order: 0,
    is_active: true,
});

function startCreate() {
    editing.value = null;
    form.defaults({
        name: '',
        description: '',
        icon: 'sparkles',
        color: '#7E5EBF',
        sort_order: (props.categories.at(-1)?.sort_order ?? 0) + 10,
        is_active: true,
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function startEdit(category: Category) {
    editing.value = category;
    form.defaults({
        name: category.name,
        description: category.description ?? '',
        icon: category.icon,
        color: category.color,
        sort_order: category.sort_order,
        is_active: category.is_active,
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function submit() {
    const done = { onSuccess: () => (formOpen.value = false) };
    editing.value ? form.put(`/admin/categories/${editing.value.id}`, done) : form.post('/admin/categories', done);
}

function performDelete() {
    if (!toDelete.value) return;
    router.delete(`/admin/categories/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => (toDelete.value = null),
    });
}
</script>

<template>
    <Head title="Categories" />

    <AdminLayout>
        <UiPageHeader title="Categories" subtitle="Each step belongs to one. The category owns the colour." />

        <UiTable :empty="categories.length === 0" empty-title="No categories yet">
            <template #toolbar>
                <UiSpinner v-if="searching" size="xs" tone="primary" class="mr-2" />
                <UiSearchInput v-model="search" placeholder="Search categories…" />
                <UiButton @click="startCreate">
                    <PlusIcon class="h-3.5 w-3.5" />
                    New category
                </UiButton>
            </template>

            <template #header>
                <UiSortableTableHeader
                    sort-key="name"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Name
                </UiSortableTableHeader>
                <UiTableHeader>Icon</UiTableHeader>
                <UiSortableTableHeader
                    sort-key="template_steps_count"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Steps
                </UiSortableTableHeader>
                <UiSortableTableHeader
                    sort-key="sort_order"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Order
                </UiSortableTableHeader>
                <UiTableHeader align="right">Actions</UiTableHeader>
            </template>

            <template #body>
                <UiTableRow v-for="category in categories" :key="category.id">
                    <UiTableCell>
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: category.color }" />
                            <span class="font-medium text-slate-900">{{ category.name }}</span>
                        </div>
                        <p v-if="category.description" class="text-label text-slate-400">
                            {{ category.description }}
                        </p>
                    </UiTableCell>
                    <UiTableCell cell-class="text-slate-500">{{ category.icon }}</UiTableCell>
                    <UiTableCell align="right">{{ category.template_steps_count }}</UiTableCell>
                    <UiTableCell align="right">{{ category.sort_order }}</UiTableCell>
                    <UiTableCell align="right">
                        <div class="flex items-center justify-end gap-2">
                            <UiActionButton title="Edit" size="sm" @click="startEdit(category)">
                                <PencilSquareIcon class="h-4 w-4" />
                            </UiActionButton>
                            <UiActionButton title="Delete" size="sm" @click="toDelete = category">
                                <TrashIcon class="h-4 w-4" />
                            </UiActionButton>
                        </div>
                    </UiTableCell>
                </UiTableRow>
            </template>
        </UiTable>

        <UiModal v-model="formOpen" :title="editing ? 'Edit category' : 'New category'">
            <form id="category-form" class="flex flex-col gap-4" @submit.prevent="submit">
                <UiInput v-model="form.name" label="Name" required :error="form.errors.name" />
                <UiInput v-model="form.description" label="Description" :error="form.errors.description" />
                <UiSelect
                    v-model="form.icon"
                    label="Icon"
                    required
                    :error="form.errors.icon"
                    :options="icons.map((i) => ({ value: i, label: i }))"
                />
                <UiInput v-model="form.color" label="Colour" required hint="Hex, e.g. #7E5EBF" :error="form.errors.color" />
                <UiInput v-model="form.sort_order" type="number" label="Order" required :error="form.errors.sort_order" />
                <UiSwitch v-model="form.is_active" label="Active" />
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <UiButton variant="outline" @click="formOpen = false">Cancel</UiButton>
                    <UiButton type="submit" form="category-form" :loading="form.processing">Submit</UiButton>
                </div>
            </template>
        </UiModal>

        <UiConfirmationModal
            :model-value="!!toDelete"
            title="Delete category?"
            :message="toDelete ? `“${toDelete.name}” will be removed from the catalogue.` : ''"
            confirm-text="Delete"
            confirm-variant="danger"
            @update:model-value="(v: boolean) => { if (!v) toDelete = null }"
            @confirm="performDelete"
        />
    </AdminLayout>
</template>
