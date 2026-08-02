<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import ChildShell from '../../../../components/children/ChildShell.vue';
import UiBadge from '../../../../components/ui/UiBadge.vue';
import UiButton from '../../../../components/ui/UiButton.vue';
import UiTable from '../../../../components/ui/UiTable.vue';
import UiTableRow from '../../../../components/ui/UiTableRow.vue';
import UiTableCell from '../../../../components/ui/UiTableCell.vue';
import UiTableHeader from '../../../../components/ui/UiTableHeader.vue';
import type { ChildSummary } from '../../../../types/admin';
import { formatDate } from '../../../../support/date';

defineProps<{
    summary: ChildSummary;
    rewards: {
        id: number;
        type: string;
        status: string;
        trophy: string | null;
        claimed_at: string | null;
        generated_at: string | null;
        has_content: boolean;
    }[];
}>();

const statusTone: Record<string, 'neutral' | 'primary' | 'success' | 'danger'> = {
    unclaimed: 'neutral',
    generating: 'primary',
    ready: 'success',
    failed: 'danger',
};

function resetGift(id: number) {
    router.post(`/admin/gifts/${id}/reset`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`${summary.child.name} · Gifts`" />

    <ChildShell :summary="summary" tab="gifts">
        <UiTable
            :empty="rewards.length === 0"
            empty-title="No gifts earned yet"
            empty-description="A gift is reserved when a trophy that carries one is unlocked."
        >
            <template #header>
                <UiTableHeader>Trophy</UiTableHeader>
                <UiTableHeader>Type</UiTableHeader>
                <UiTableHeader>Status</UiTableHeader>
                <UiTableHeader align="right">Claimed</UiTableHeader>
                <UiTableHeader align="right" />
            </template>
            <template #body>
                <UiTableRow v-for="reward in rewards" :key="reward.id">
                    <UiTableCell cell-class="font-medium text-slate-900">{{ reward.trophy }}</UiTableCell>
                    <UiTableCell>{{ reward.type }}</UiTableCell>
                    <UiTableCell>
                        <UiBadge :tone="statusTone[reward.status] ?? 'neutral'">{{ reward.status }}</UiBadge>
                    </UiTableCell>
                    <UiTableCell align="right" cell-class="text-slate-500">
                        {{ formatDate(reward.claimed_at) }}
                    </UiTableCell>
                    <UiTableCell align="right">
                        <UiButton v-if="reward.status !== 'unclaimed'" variant="outline" @click="resetGift(reward.id)">
                            Reset
                        </UiButton>
                    </UiTableCell>
                </UiTableRow>
            </template>
        </UiTable>
    </ChildShell>
</template>
