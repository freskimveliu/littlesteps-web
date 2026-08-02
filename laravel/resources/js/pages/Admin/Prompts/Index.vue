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
import UiPagination from '../../../components/ui/UiPagination.vue';
import { useIndexFilters } from '../../../composables/useIndexFilters';

interface Prompt {
    id: number;
    name: string;
    icon: string | null;
    months_from: number | null;
    months_to: number | null;
    sort_order: number;
    is_active: boolean;
    category_id: number | null;
    category: { id: number; name: string; color: string } | null;
}

const props = defineProps<{
    prompts: { data: Prompt[]; current_page: number; last_page: number; total: number; per_page: number };
    filters: { search: string | null; sort: string | null; order: string | null };
    categories: { id: number; name: string; color: string }[];
    icons: string[];
}>();

const { search, searching, sortKey, sortOrder, toggleSort, visit } = useIndexFilters({
    url: '/admin/prompts',
    search: props.filters.search,
    sortKey: props.filters.sort,
    sortOrder: props.filters.order as 'asc' | 'desc' | null,
});

const formOpen = ref(false);
const editing = ref<Prompt | null>(null);
const toDelete = ref<Prompt | null>(null);

const form = useForm({
    category_id: (props.categories[0]?.id ?? null) as number | null,
    name: '',
    icon: '',
    months_from: null as number | null,
    months_to: null as number | null,
    sort_order: 0,
    is_active: true,
});

function startCreate() {
    editing.value = null;
    form.defaults({
        category_id: props.categories[0]?.id ?? null,
        name: '',
        icon: '',
        months_from: null,
        months_to: null,
        sort_order: (props.prompts.data.at(-1)?.sort_order ?? 0) + 10,
        is_active: true,
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function startEdit(prompt: Prompt) {
    editing.value = prompt;
    form.defaults({
        category_id: prompt.category_id,
        name: prompt.name,
        icon: prompt.icon ?? '',
        months_from: prompt.months_from,
        months_to: prompt.months_to,
        sort_order: prompt.sort_order,
        is_active: prompt.is_active,
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function submit() {
    const done = { onSuccess: () => (formOpen.value = false) };
    editing.value ? form.put(`/admin/prompts/${editing.value.id}`, done) : form.post('/admin/prompts', done);
}

function performDelete() {
    if (!toDelete.value) return;
    router.delete(`/admin/prompts/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => (toDelete.value = null),
    });
}

function range(prompt: Prompt): string {
    if (prompt.months_from === null && prompt.months_to === null) return 'Any age';
    return `${prompt.months_from ?? 0}–${prompt.months_to ?? '∞'} mo`;
}
</script>

<template>
    <Head title="Prompts" />

    <AdminLayout>
        <UiPageHeader title="Prompts" />

        <UiTable :empty="prompts.data.length === 0" empty-title="No prompts match">
            <template #toolbar>
                <UiSpinner v-if="searching" size="xs" tone="primary" class="mr-2" />
                <UiSearchInput v-model="search" placeholder="Search prompts…" />
                <UiButton @click="startCreate">
                    <PlusIcon class="h-3.5 w-3.5" />
                    New prompt
                </UiButton>
            </template>

            <template #header>
                <UiSortableTableHeader sort-key="name" :active-key="sortKey" :active-order="sortOrder" @sort="toggleSort">
                    Prompt
                </UiSortableTableHeader>
                <UiTableHeader>Category</UiTableHeader>
                <UiSortableTableHeader
                    sort-key="months_from"
                    align="right"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Shown at
                </UiSortableTableHeader>
                <UiTableHeader align="right">Actions</UiTableHeader>
            </template>

            <template #body>
                <UiTableRow v-for="prompt in prompts.data" :key="prompt.id">
                    <UiTableCell cell-class="max-w-md">
                        <span class="text-slate-800">{{ prompt.name }}</span>
                    </UiTableCell>
                    <UiTableCell>
                        <div v-if="prompt.category" class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: prompt.category.color }" />
                            <span class="text-slate-600">{{ prompt.category.name }}</span>
                        </div>
                    </UiTableCell>
                    <UiTableCell align="right" cell-class="text-slate-500">{{ range(prompt) }}</UiTableCell>
                    <UiTableCell align="right">
                        <div class="flex items-center justify-end gap-2">
                            <UiActionButton title="Edit" size="sm" @click="startEdit(prompt)">
                                <PencilSquareIcon class="h-4 w-4" />
                            </UiActionButton>
                            <UiActionButton title="Delete" size="sm" @click="toDelete = prompt">
                                <TrashIcon class="h-4 w-4" />
                            </UiActionButton>
                        </div>
                    </UiTableCell>
                </UiTableRow>
            </template>

            <template #footer>
                <UiPagination
                    v-if="prompts.last_page > 1"
                    :current-page="prompts.current_page"
                    :last-page="prompts.last_page"
                    :total="prompts.total"
                    :per-page="prompts.per_page"
                    @change="(p: number) => visit({ page: p })"
                />
            </template>
        </UiTable>

        <UiModal v-model="formOpen" :title="editing ? 'Edit prompt' : 'New prompt'">
            <form id="prompt-form" class="flex flex-col gap-4" @submit.prevent="submit">
                <UiInput v-model="form.name" label="Prompt" required :error="form.errors.name" />

                <UiSelect
                    v-model="form.category_id"
                    label="Category"
                    :error="form.errors.category_id"
                    :options="categories.map((c) => ({ value: c.id, label: c.name }))"
                />

                <UiSelect
                    v-model="form.icon"
                    label="Icon"
                    :error="form.errors.icon"
                    :options="[{ value: '', label: '— none —' }, ...icons.map((i) => ({ value: i, label: i }))]"
                />

                <div class="grid grid-cols-3 gap-3">
                    <UiInput
                        v-model="form.months_from"
                        type="number"
                        label="From (months)"
                        :error="form.errors.months_from"
                    />
                    <UiInput
                        v-model="form.months_to"
                        type="number"
                        label="To (months)"
                        hint="Keeps a newborn prompt off a five-year-old"
                        :error="form.errors.months_to"
                    />
                    <UiInput
                        v-model="form.sort_order"
                        type="number"
                        label="Order"
                        required
                        :error="form.errors.sort_order"
                    />
                </div>

                <UiSwitch v-model="form.is_active" label="Active" />
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <UiButton variant="outline" @click="formOpen = false">Cancel</UiButton>
                    <UiButton type="submit" form="prompt-form" :loading="form.processing">Submit</UiButton>
                </div>
            </template>
        </UiModal>

        <UiConfirmationModal
            :model-value="!!toDelete"
            title="Delete prompt?"
            message="It will stop appearing on Home."
            confirm-text="Delete"
            confirm-variant="danger"
            @update:model-value="(v: boolean) => { if (!v) toDelete = null }"
            @confirm="performDelete"
        />
    </AdminLayout>
</template>
