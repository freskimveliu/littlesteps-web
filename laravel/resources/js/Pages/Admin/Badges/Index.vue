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

interface Badge {
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
    badges: Badge[];
    filters: { search: string | null; sort: string | null; order: string | null };
    metrics: string[];
    rewards: string[];
    icons: string[];
}>();

const { search, searching, sortKey, sortOrder, toggleSort } = useIndexFilters({
    url: '/admin/badges',
    search: props.filters.search,
    sortKey: props.filters.sort,
    sortOrder: props.filters.order as 'asc' | 'desc' | null,
});

const metricHints: Record<string, string> = {
    days: 'Distinct days with a memory',
    months: 'Distinct calendar months with a memory',
    streak: 'Consecutive days',
    on_time_steps: 'Steps recorded while the child was actually that age',
    milestones: 'Milestones the parent has finished',
    photos: 'Photos kept',
    categories: 'Different categories used',
};

const rewardTone = { story: 'primary', image: 'success', book: 'gold' } as const;

const formOpen = ref(false);
const editing = ref<Badge | null>(null);
const toDelete = ref<Badge | null>(null);

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
        sort_order: (props.badges.at(-1)?.sort_order ?? 0) + 10,
        is_active: true,
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function startEdit(badge: Badge) {
    editing.value = badge;
    form.defaults({
        name: badge.name,
        description: badge.description ?? '',
        icon: badge.icon,
        metric: badge.metric,
        threshold: badge.threshold,
        xp: badge.xp,
        reward: badge.reward ?? '',
        sort_order: badge.sort_order,
        is_active: badge.is_active,
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function submit() {
    const done = { onSuccess: () => (formOpen.value = false) };
    const payload = { ...form.data(), reward: form.reward || null };
    form.transform(() => payload);
    editing.value ? form.put(`/admin/badges/${editing.value.id}`, done) : form.post('/admin/badges', done);
}

function performDelete() {
    if (!toDelete.value) return;
    router.delete(`/admin/badges/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => (toDelete.value = null),
    });
}
</script>

<template>
    <Head title="Badges" />

    <AdminLayout>
        <UiPageHeader
            title="Badges"
            subtitle="Every metric is gated by the calendar. Retuning a threshold never takes a badge back."
        />

        <UiTable :empty="badges.length === 0" empty-title="No badges yet">
            <template #toolbar>
                <UiSpinner v-if="searching" size="xs" tone="primary" class="mr-2" />
                <UiSearchInput v-model="search" placeholder="Search badges…" />
                <UiButton @click="startCreate">
                    <PlusIcon class="h-3.5 w-3.5" />
                    New badge
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
                <UiTableRow v-for="badge in badges" :key="badge.id">
                    <UiTableCell>
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-slate-900">{{ badge.name }}</span>
                            <UiBadge v-if="!badge.is_active" tone="danger">inactive</UiBadge>
                        </div>
                        <p v-if="badge.description" class="text-label text-slate-400">{{ badge.description }}</p>
                    </UiTableCell>
                    <UiTableCell>
                        <span class="font-medium text-slate-700">{{ badge.metric }} ≥ {{ badge.threshold }}</span>
                        <p class="text-label text-slate-400">{{ metricHints[badge.metric] }}</p>
                    </UiTableCell>
                    <UiTableCell align="right">{{ badge.xp }}</UiTableCell>
                    <UiTableCell>
                        <UiBadge v-if="badge.reward" :tone="rewardTone[badge.reward as keyof typeof rewardTone]">
                            {{ badge.reward }}
                        </UiBadge>
                        <span v-else class="text-slate-300">—</span>
                    </UiTableCell>
                    <UiTableCell align="right">
                        <div class="flex items-center justify-end gap-2">
                            <UiActionButton title="Edit" size="sm" @click="startEdit(badge)">
                                <PencilSquareIcon class="h-4 w-4" />
                            </UiActionButton>
                            <UiActionButton title="Delete" size="sm" @click="toDelete = badge">
                                <TrashIcon class="h-4 w-4" />
                            </UiActionButton>
                        </div>
                    </UiTableCell>
                </UiTableRow>
            </template>
        </UiTable>

        <UiModal v-model="formOpen" :title="editing ? 'Edit badge' : 'New badge'">
            <form id="badge-form" class="flex flex-col gap-4" @submit.prevent="submit">
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
                    <UiButton type="submit" form="badge-form" :loading="form.processing">Submit</UiButton>
                </div>
            </template>
        </UiModal>

        <UiConfirmationModal
            :model-value="!!toDelete"
            title="Remove badge?"
            :message="toDelete ? `Children who already earned “${toDelete.name}” keep it.` : ''"
            confirm-text="Remove"
            confirm-variant="danger"
            @update:model-value="(v: boolean) => { if (!v) toDelete = null }"
            @confirm="performDelete"
        />
    </AdminLayout>
</template>
