<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ChildShell from '../../../../components/children/ChildShell.vue';
import UiBadge from '../../../../components/ui/UiBadge.vue';
import UiTable from '../../../../components/ui/UiTable.vue';
import UiTableRow from '../../../../components/ui/UiTableRow.vue';
import UiTableCell from '../../../../components/ui/UiTableCell.vue';
import UiTableHeader from '../../../../components/ui/UiTableHeader.vue';
import type { ChildSummary } from '../../../../types/admin';
import { formatDate } from '../../../../support/date';

defineProps<{
    summary: ChildSummary;
    trophies: {
        id: number;
        name: string;
        metric: string;
        threshold: number;
        reward: string | null;
        progress: number;
        unlocked_at: string | null;
        is_retired: boolean;
    }[];
}>();
</script>

<template>
    <Head :title="`${summary.child.name} · Trophies`" />

    <ChildShell :summary="summary" tab="trophies">
        <UiTable :empty="trophies.length === 0" empty-title="No trophies configured">
            <template #header>
                <UiTableHeader>Trophy</UiTableHeader>
                <UiTableHeader>Rule</UiTableHeader>
                <UiTableHeader>Progress</UiTableHeader>
                <UiTableHeader>Gift</UiTableHeader>
                <UiTableHeader align="right">Earned</UiTableHeader>
            </template>
            <template #body>
                <UiTableRow v-for="trophy in trophies" :key="trophy.id">
                    <UiTableCell cell-class="font-medium text-slate-900">
                        <div class="flex items-center gap-2">
                            {{ trophy.name }}
                            <UiBadge v-if="trophy.is_retired" tone="neutral" title="No longer in the catalogue">
                                retired
                            </UiBadge>
                        </div>
                    </UiTableCell>
                    <UiTableCell cell-class="text-slate-500">{{ trophy.metric }} ≥ {{ trophy.threshold }}</UiTableCell>
                    <UiTableCell>
                        <div class="flex items-center gap-2">
                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full"
                                    :class="trophy.unlocked_at ? 'bg-emerald-500' : 'bg-primary'"
                                    :style="{ width: `${(trophy.progress / trophy.threshold) * 100}%` }"
                                />
                            </div>
                            <span class="text-slate-500">{{ trophy.progress }} / {{ trophy.threshold }}</span>
                        </div>
                    </UiTableCell>
                    <UiTableCell>
                        <UiBadge v-if="trophy.reward" tone="gold">{{ trophy.reward }}</UiBadge>
                        <span v-else class="text-slate-300">—</span>
                    </UiTableCell>
                    <UiTableCell align="right">
                        <UiBadge v-if="trophy.unlocked_at" tone="success">
                            {{ formatDate(trophy.unlocked_at) }}
                        </UiBadge>
                        <span v-else class="text-slate-300">—</span>
                    </UiTableCell>
                </UiTableRow>
            </template>
        </UiTable>
    </ChildShell>
</template>
