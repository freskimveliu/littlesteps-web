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
import UiBadge from '../../../components/ui/UiBadge.vue';
import UiModal from '../../../components/ui/UiModal.vue';
import UiInput from '../../../components/ui/UiInput.vue';
import UiSelect from '../../../components/ui/UiSelect.vue';
import UiSwitch from '../../../components/ui/UiSwitch.vue';
import UiConfirmationModal from '../../../components/ui/UiConfirmationModal.vue';
import { useIndexFilters } from '../../../composables/useIndexFilters';

interface Trophy {
    id: number;
    name: string;
    description: string | null;
    icon: string;
    metric: string;
    threshold: number;
    xp: number;
    reward: string | null;
    sort_order: number;
    is_active: boolean;
}

const props = defineProps<{
    trophies: Trophy[];
    filters: { search: string | null; sort: string | null; order: string | null };
    metrics: string[];
    rewards: string[];
    icons: string[];
}>();

const { search, searching, sortKey, sortOrder, toggleSort } = useIndexFilters({
    url: '/admin/trophies',
    search: props.filters.search,
    sortKey: props.filters.sort,
    sortOrder: props.filters.order as 'asc' | 'desc' | null,
});

const metricHints: Record<string, string> = {
    days: 'Distinct days with a memory',
    months: 'Distinct calendar months with a memory',
    streak: 'Consecutive days',
    on_time_milestones: 'Milestones recorded while the child was actually that age',
    chapters: 'Chapters the parent has finished',
    photos: 'Photos kept',
    categories: 'Different categories used',
};

const rewardTone = { story: 'primary', image: 'success', book: 'gold' } as const;

const formOpen = ref(false);
const editing = ref<Trophy | null>(null);
const toDelete = ref<Trophy | null>(null);

const form = useForm({
    name: '',
    description: '',
    icon: 'star',
    metric: 'days',
    threshold: 7,
    xp: 60,
    reward: '',
    sort_order: 0,
    is_active: true,
});

function startCreate() {
    editing.value = null;
    form.defaults({
        name: '',
        description: '',
        icon: 'star',
        metric: 'days',
        threshold: 7,
        xp: 60,
        reward: '',
        sort_order: (props.trophies.at(-1)?.sort_order ?? 0) + 10,
        is_active: true,
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function startEdit(trophy: Trophy) {
    editing.value = trophy;
    form.defaults({
        name: trophy.name,
        description: trophy.description ?? '',
        icon: trophy.icon,
        metric: trophy.metric,
        threshold: trophy.threshold,
        xp: trophy.xp,
        reward: trophy.reward ?? '',
        sort_order: trophy.sort_order,
        is_active: trophy.is_active,
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function submit() {
    const done = { onSuccess: () => (formOpen.value = false) };
    const payload = { ...form.data(), reward: form.reward || null };
    form.transform(() => payload);
    editing.value ? form.put(`/admin/trophies/${editing.value.id}`, done) : form.post('/admin/trophies', done);
}

function performDelete() {
    if (!toDelete.value) return;
    router.delete(`/admin/trophies/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => (toDelete.value = null),
    });
}
</script>

<template>
    <Head title="Trophies" />

    <AdminLayout>
        <UiPageHeader title="Trophies" />

        <UiTable :empty="trophies.length === 0" empty-title="No trophies yet">
            <template #toolbar>
                <UiSpinner v-if="searching" size="xs" tone="primary" class="mr-2" />
                <UiSearchInput v-model="search" placeholder="Search trophies…" />
                <UiButton @click="startCreate">
                    <PlusIcon class="h-3.5 w-3.5" />
                    New trophy
                </UiButton>
            </template>

            <template #header>
                <UiSortableTableHeader sort-key="name" :active-key="sortKey" :active-order="sortOrder" @sort="toggleSort">
                    Name
                </UiSortableTableHeader>
                <UiSortableTableHeader
                    sort-key="metric"
                    :active-key="sortKey"
                    :active-order="sortOrder"
                    @sort="toggleSort"
                >
                    Rule
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
                <UiTableHeader>Gift</UiTableHeader>
                <UiTableHeader align="right">Actions</UiTableHeader>
            </template>

            <template #body>
                <UiTableRow v-for="trophy in trophies" :key="trophy.id">
                    <UiTableCell>
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-slate-900">{{ trophy.name }}</span>
                            <UiBadge v-if="!trophy.is_active" tone="danger">inactive</UiBadge>
                        </div>
                        <p v-if="trophy.description" class="text-label text-slate-400">{{ trophy.description }}</p>
                    </UiTableCell>
                    <UiTableCell>
                        <span class="font-medium text-slate-700">{{ trophy.metric }} ≥ {{ trophy.threshold }}</span>
                        <p class="text-label text-slate-400">{{ metricHints[trophy.metric] }}</p>
                    </UiTableCell>
                    <UiTableCell align="right">{{ trophy.xp }}</UiTableCell>
                    <UiTableCell>
                        <UiBadge v-if="trophy.reward" :tone="rewardTone[trophy.reward as keyof typeof rewardTone]">
                            {{ trophy.reward }}
                        </UiBadge>
                        <span v-else class="text-slate-300">—</span>
                    </UiTableCell>
                    <UiTableCell align="right">
                        <div class="flex items-center justify-end gap-2">
                            <UiActionButton title="Edit" size="sm" @click="startEdit(trophy)">
                                <PencilSquareIcon class="h-4 w-4" />
                            </UiActionButton>
                            <UiActionButton title="Delete" size="sm" @click="toDelete = trophy">
                                <TrashIcon class="h-4 w-4" />
                            </UiActionButton>
                        </div>
                    </UiTableCell>
                </UiTableRow>
            </template>
        </UiTable>

        <UiModal v-model="formOpen" :title="editing ? 'Edit trophy' : 'New trophy'">
            <form id="trophy-form" class="flex flex-col gap-4" @submit.prevent="submit">
                <div class="grid grid-cols-2 gap-3">
                    <UiInput v-model="form.name" label="Name" required :error="form.errors.name" />
                </div>

                <UiInput v-model="form.description" label="Description" :error="form.errors.description" />

                <UiSelect
                    v-model="form.icon"
                    label="Icon"
                    required
                    :error="form.errors.icon"
                    :options="icons.map((i) => ({ value: i, label: i }))"
                />

                <div class="grid grid-cols-2 gap-3">
                    <UiSelect
                        v-model="form.metric"
                        label="Metric"
                        required
                        :hint="metricHints[form.metric]"
                        :error="form.errors.metric"
                        :options="metrics.map((m) => ({ value: m, label: m }))"
                    />
                    <UiInput
                        v-model="form.threshold"
                        type="number"
                        label="Threshold"
                        required
                        :error="form.errors.threshold"
                    />
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <UiInput v-model="form.xp" type="number" label="XP" required :error="form.errors.xp" />
                    <UiSelect
                        v-model="form.reward"
                        label="Gift"
                        hint="Costs a generation when claimed"
                        :error="form.errors.reward"
                        :options="[{ value: '', label: '— none —' }, ...rewards.map((r) => ({ value: r, label: r }))]"
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
                    <UiButton type="submit" form="trophy-form" :loading="form.processing">Submit</UiButton>
                </div>
            </template>
        </UiModal>

        <UiConfirmationModal
            :model-value="!!toDelete"
            title="Remove trophy?"
            :message="toDelete ? `Children who already earned “${toDelete.name}” keep it.` : ''"
            confirm-text="Remove"
            confirm-variant="danger"
            @update:model-value="(v: boolean) => { if (!v) toDelete = null }"
            @confirm="performDelete"
        />
    </AdminLayout>
</template>
