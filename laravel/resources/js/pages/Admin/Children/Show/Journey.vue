<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import ChevronRightIcon from '@heroicons/vue/24/outline/esm/ChevronRightIcon.js';
import CheckCircleIcon from '@heroicons/vue/24/solid/esm/CheckCircleIcon.js';
import LockClosedIcon from '@heroicons/vue/24/outline/esm/LockClosedIcon.js';
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
    chapters: {
        id: number;
        name: string;
        months_from: number | null;
        xp: number;
        is_hidden: boolean;
        completed_at: string | null;
        milestones_total: number;
        milestones_recorded: number;
        milestones: {
            id: number;
            name: string;
            months_from: number | null;
            xp: number;
            is_hidden: boolean;
            is_custom: boolean;
            is_locked: boolean;
            recorded_on: string | null;
        }[];
    }[];
}>();

const expanded = ref<number | null>(null);

function toggleChapter(id: number) {
    expanded.value = expanded.value === id ? null : id;
}
</script>

<template>
    <Head :title="summary.child.name" />

    <ChildShell :summary="summary" tab="journey">
        <UiTable :empty="chapters.length === 0" empty-title="Not provisioned">
            <template #header>
                <UiTableHeader>Chapter</UiTableHeader>
                <UiTableHeader align="right">Opens at</UiTableHeader>
                <UiTableHeader>Progress</UiTableHeader>
                <UiTableHeader align="right">Finished</UiTableHeader>
            </template>
            <template #body>
                <template v-for="chapter in chapters" :key="chapter.id">
                    <UiTableRow class="cursor-pointer" @click="toggleChapter(chapter.id)">
                        <UiTableCell>
                            <div class="flex items-center gap-2">
                                <ChevronRightIcon
                                    class="h-3.5 w-3.5 text-slate-400 transition-transform"
                                    :class="expanded === chapter.id ? 'rotate-90' : ''"
                                />
                                <span class="font-medium text-slate-900">{{ chapter.name }}</span>
                                <UiBadge v-if="chapter.is_hidden" tone="neutral">hidden</UiBadge>
                            </div>
                        </UiTableCell>
                        <UiTableCell align="right" cell-class="text-slate-500">
                            {{ chapter.months_from }} mo
                        </UiTableCell>
                        <UiTableCell>
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-32 overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full bg-primary"
                                        :style="{
                                            width: `${chapter.milestones_total ? (chapter.milestones_recorded / chapter.milestones_total) * 100 : 0}%`,
                                        }"
                                    />
                                </div>
                                <span class="text-slate-500">
                                    {{ chapter.milestones_recorded }} / {{ chapter.milestones_total }}
                                </span>
                            </div>
                        </UiTableCell>
                        <UiTableCell align="right">
                            <UiBadge v-if="chapter.completed_at" tone="success">
                                {{ formatDate(chapter.completed_at) }}
                            </UiBadge>
                            <span v-else class="text-slate-300">—</span>
                        </UiTableCell>
                    </UiTableRow>

                    <tr v-if="expanded === chapter.id" class="bg-slate-50/60">
                        <td colspan="4" class="px-6 py-4">
                            <p v-if="!chapter.milestones.length" class="text-body text-slate-400">
                                No milestones in this chapter.
                            </p>
                            <div v-else class="flex flex-col gap-1.5">
                                <div
                                    v-for="milestone in chapter.milestones"
                                    :key="milestone.id"
                                    class="flex items-center gap-2 text-body"
                                >
                                    <CheckCircleIcon
                                        v-if="milestone.recorded_on"
                                        class="h-4 w-4 flex-shrink-0 text-emerald-500"
                                    />
                                    <LockClosedIcon
                                        v-else-if="milestone.is_locked"
                                        class="h-4 w-4 flex-shrink-0 text-slate-300"
                                    />
                                    <span v-else class="h-4 w-4 flex-shrink-0 rounded-full border border-slate-200" />

                                    <span :class="milestone.recorded_on ? 'text-slate-800' : 'text-slate-500'">
                                        {{ milestone.name }}
                                    </span>
                                    <UiBadge v-if="milestone.is_custom" tone="primary">own</UiBadge>
                                    <UiBadge v-if="milestone.is_hidden" tone="neutral">hidden</UiBadge>

                                    <span class="ml-auto text-slate-400">
                                        {{
                                            milestone.recorded_on
                                                ? formatDate(milestone.recorded_on)
                                                : `${milestone.months_from ?? 0} mo`
                                        }}
                                    </span>
                                </div>
                            </div>
                        </td>
                    </tr>
                </template>
            </template>
        </UiTable>
    </ChildShell>
</template>
