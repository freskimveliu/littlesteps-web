<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import UiPageHeader from '../../../components/ui/UiPageHeader.vue';
import UiTable from '../../../components/ui/UiTable.vue';
import UiTableRow from '../../../components/ui/UiTableRow.vue';
import UiTableCell from '../../../components/ui/UiTableCell.vue';
import UiTableHeader from '../../../components/ui/UiTableHeader.vue';
import UiButton from '../../../components/ui/UiButton.vue';
import UiBadge from '../../../components/ui/UiBadge.vue';

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

const tabs = [
    { key: null, label: 'All' },
    { key: 'unclaimed', label: 'Unclaimed' },
    { key: 'generating', label: 'Generating' },
    { key: 'ready', label: 'Ready' },
    { key: 'failed', label: 'Failed' },
];

function pick(status: string | null) {
    router.get('/admin/gifts', { status: status ?? undefined }, { preserveState: false });
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
        <UiPageHeader
            title="Gifts"
            :subtitle="`${gifts.total} earned. Nothing generates until a parent claims it — a failure leaves a row here and nowhere else.`"
        />

        <div class="mb-4 flex flex-wrap gap-2">
            <UiButton
                v-for="tab in tabs"
                :key="tab.label"
                variant="outline"
                :active="(filters.status ?? null) === tab.key"
                @click="pick(tab.key)"
            >
                {{ tab.label }}
                <UiBadge v-if="tab.key" tone="neutral">{{ counts[tab.key] ?? 0 }}</UiBadge>
            </UiButton>
        </div>

        <UiTable
            :empty="gifts.data.length === 0"
            empty-title="No gifts here"
            empty-description="A gift is reserved the moment a badge that carries one is unlocked."
        >
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
                <div
                    v-if="gifts.last_page > 1"
                    class="flex items-center justify-between border-t border-[#f0f4f8] px-6 py-4 text-body text-slate-500"
                >
                    <span>Page {{ gifts.current_page }} of {{ gifts.last_page }}</span>
                    <div class="flex gap-2">
                        <UiButton variant="outline" :disabled="gifts.current_page === 1" @click="page(gifts.current_page - 1)">
                            Previous
                        </UiButton>
                        <UiButton
                            variant="outline"
                            :disabled="gifts.current_page === gifts.last_page"
                            @click="page(gifts.current_page + 1)"
                        >
                            Next
                        </UiButton>
                    </div>
                </div>
            </template>
        </UiTable>
    </AdminLayout>
</template>
