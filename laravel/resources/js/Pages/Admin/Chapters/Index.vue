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

interface Chapter {
    id: number;
    slug: string;
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
    chapters: Chapter[];
    filters: { search: string | null; sort: string | null; order: string | null };
    icons: string[];
}>();

const { search, searching, sortKey, sortOrder, toggleSort } = useIndexFilters({
    url: '/admin/chapters',
    search: props.filters.search,
    sortKey: props.filters.sort,
    sortOrder: props.filters.order as 'asc' | 'desc' | null,
});

const formOpen = ref(false);
const editing = ref<Chapter | null>(null);
const toDelete = ref<Chapter | null>(null);

const form = useForm({
    slug: '',
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
        slug: '',
        name: '',
        description: '',
        icon: 'star',
        months_from: 0,
        xp: 150,
        sort_order: (props.chapters.at(-1)?.sort_order ?? 0) + 10,
        is_editable: false,
        is_active: true,
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function startEdit(chapter: Chapter) {
    editing.value = chapter;
    form.defaults({
        slug: chapter.slug,
        name: chapter.name,
        description: chapter.description ?? '',
        icon: chapter.icon,
        months_from: chapter.months_from ?? 0,
        xp: chapter.xp,
        sort_order: chapter.sort_order,
        is_editable: chapter.is_editable,
        is_active: chapter.is_active,
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function submit() {
    const done = { onSuccess: () => (formOpen.value = false) };
    editing.value ? form.put(`/admin/chapters/${editing.value.id}`, done) : form.post('/admin/chapters', done);
}

function performDelete() {
    if (!toDelete.value) return;
    router.delete(`/admin/chapters/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => (toDelete.value = null),
    });
}
</script>

<template>
    <Head title="Chapters" />

    <AdminLayout>
        <UiPageHeader
            title="Chapters"
            subtitle="The eight parts of the journey. A chapter opens at its months_from and is finished by the parent."
        />

        <UiTable :empty="chapters.length === 0" empty-title="No chapters yet">
            <template #toolbar>
                <UiSpinner v-if="searching" size="xs" tone="primary" class="mr-2" />
                <UiSearchInput v-model="search" placeholder="Search chapters…" />
                <UiButton @click="startCreate">
                    <PlusIcon class="h-3.5 w-3.5" />
                    New chapter
                </UiButton>
            </template>

            <template #header>
                <UiSortableTableHeader sort-key="name" :active-key="sortKey" :active-order="sortOrder" @sort="toggleSort">
                    Name
                </UiSortableTableHeader>
                <UiTableHeader>Slug</UiTableHeader>
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
                <UiTableRow v-for="chapter in chapters" :key="chapter.id">
                    <UiTableCell>
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-slate-900">{{ chapter.name }}</span>
                            <UiBadge v-if="!chapter.is_active" tone="danger">inactive</UiBadge>
                        </div>
                        <p v-if="chapter.description" class="text-label text-slate-400">{{ chapter.description }}</p>
                    </UiTableCell>
                    <UiTableCell cell-class="text-slate-500">{{ chapter.slug }}</UiTableCell>
                    <UiTableCell align="right">{{ chapter.months_from }} mo</UiTableCell>
                    <UiTableCell align="right">
                        <Link
                            :href="`/admin/steps?chapter=${chapter.id}`"
                            class="text-primary-accessible hover:underline"
                        >
                            {{ chapter.steps_count }}
                        </Link>
                    </UiTableCell>
                    <UiTableCell align="right">{{ chapter.xp }}</UiTableCell>
                    <UiTableCell align="right">
                        <div class="flex items-center justify-end gap-2">
                            <UiActionButton title="Edit" size="sm" @click="startEdit(chapter)">
                                <PencilSquareIcon class="h-4 w-4" />
                            </UiActionButton>
                            <UiActionButton title="Delete" size="sm" @click="toDelete = chapter">
                                <TrashIcon class="h-4 w-4" />
                            </UiActionButton>
                        </div>
                    </UiTableCell>
                </UiTableRow>
            </template>
        </UiTable>

        <UiModal v-model="formOpen" :title="editing ? 'Edit chapter' : 'New chapter'">
            <form id="chapter-form" class="flex flex-col gap-4" @submit.prevent="submit">
                <UiInput v-model="form.name" label="Name" required :error="form.errors.name" />
                <UiInput v-model="form.slug" label="Slug" required :error="form.errors.slug" />
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
                    <UiButton type="submit" form="chapter-form" :loading="form.processing">Submit</UiButton>
                </div>
            </template>
        </UiModal>

        <UiConfirmationModal
            :model-value="!!toDelete"
            title="Remove chapter?"
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
