<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import UiPageHeader from '../../../components/ui/UiPageHeader.vue';
import UiTable from '../../../components/ui/UiTable.vue';
import UiTableRow from '../../../components/ui/UiTableRow.vue';
import UiTableCell from '../../../components/ui/UiTableCell.vue';
import UiTableHeader from '../../../components/ui/UiTableHeader.vue';
import UiButton from '../../../components/ui/UiButton.vue';
import UiBadge from '../../../components/ui/UiBadge.vue';
import UiPagination from '../../../components/ui/UiPagination.vue';
import UiFilterPopover from '../../../components/ui/UiFilterPopover.vue';
import UiSelect from '../../../components/ui/UiSelect.vue';

const props = defineProps<{
    gifts: {
        data: {
            id: number;
            type: string;
            status: string;
            child: { id: number; name: string } | null;
            badge: string | null;
            claimed_at: string | null;
            generated_at: string | null;
            is_stuck: boolean;
        }[];
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
    filters: { status: string | null };
    counts: Record<string, number>;
}>();

const statusTone: Record<string, 'neutral' | 'primary' | 'success' | 'danger'> = {
    unclaimed: 'neutral',
    generating: 'primary',
    ready: 'success',
    failed: 'danger',
};

const statusOptions = [
    { value: '', label: 'Any status' },
    { value: 'unclaimed', label: `Unclaimed (${props.counts.unclaimed ?? 0})` },
    { value: 'generating', label: `Generating (${props.counts.generating ?? 0})` },
    { value: 'ready', label: `Ready (${props.counts.ready ?? 0})` },
    { value: 'failed', label: `Failed (${props.counts.failed ?? 0})` },
];

const status = ref(props.filters.status ?? '');
const activeCount = computed(() => (props.filters.status ? 1 : 0));

function applyFilters() {
    router.get('/admin/gifts', { status: status.value || undefined }, { preserveState: false });
}

function resetFilters() {
    status.value = '';
    router.get('/admin/gifts', {}, { preserveState: false });
}

function reset(id: number) {
    router.post(`/admin/gifts/${id}/reset`, {}, { preserveScroll: true });
}

function page(n: number) {
    router.get('/admin/gifts', { status: props.filters.status ?? undefined, page: n }, { preserveState: false });
}
</script>

<template>
    <Head title="Gifts" />

    <AdminLayout>
        <UiPageHeader title="Gifts" />

        <UiTable
            :empty="gifts.data.length === 0"
            empty-title="No gifts here"
            empty-description="A gift is reserved the moment a badge that carries one is unlocked."
        >
            <template #toolbar>
                <UiFilterPopover
                    :active-count="activeCount"
                    title="Filters"
                    @apply="applyFilters"
                    @reset="resetFilters"
                >
                    <UiSelect v-model="status" label="Status" :options="statusOptions" />
                </UiFilterPopover>
            </template>

            <template #header>
                <UiTableHeader>Child</UiTableHeader>
                <UiTableHeader>Badge</UiTableHeader>
                <UiTableHeader>Type</UiTableHeader>
                <UiTableHeader>Status</UiTableHeader>
                <UiTableHeader align="right">Claimed</UiTableHeader>
                <UiTableHeader align="right" />
            </template>

            <template #body>
                <UiTableRow v-for="gift in gifts.data" :key="gift.id">
                    <UiTableCell>
                        <Link
                            v-if="gift.child"
                            :href="`/admin/children/${gift.child.id}`"
                            class="font-medium text-slate-900 hover:underline"
                        >
                            {{ gift.child.name }}
                        </Link>
                    </UiTableCell>
                    <UiTableCell cell-class="text-slate-600">{{ gift.badge }}</UiTableCell>
                    <UiTableCell>{{ gift.type }}</UiTableCell>
                    <UiTableCell>
                        <div class="flex items-center gap-2">
                            <UiBadge :tone="statusTone[gift.status] ?? 'neutral'">{{ gift.status }}</UiBadge>
                            <UiBadge v-if="gift.is_stuck" tone="danger">stuck</UiBadge>
                        </div>
                    </UiTableCell>
                    <UiTableCell align="right" cell-class="text-slate-500">
                        {{ gift.claimed_at?.slice(0, 10) ?? '—' }}
                    </UiTableCell>
                    <UiTableCell align="right">
                        <UiButton v-if="gift.status !== 'unclaimed'" variant="outline" @click="reset(gift.id)">
                            Reset
                        </UiButton>
                    </UiTableCell>
                </UiTableRow>
            </template>

            <template #footer>
                <UiPagination
                    v-if="gifts.last_page > 1"
                    :current-page="gifts.current_page"
                    :last-page="gifts.last_page"
                    :total="gifts.total"
                    :per-page="gifts.per_page"
                    @change="(p: number) => page(p)"
                />
            </template>
        </UiTable>
    </AdminLayout>
</template>
