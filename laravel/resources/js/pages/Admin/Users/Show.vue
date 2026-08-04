<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import UserShell from '../../../components/users/UserShell.vue';
import UiCard from '../../../components/ui/UiCard.vue';
import UiBadge from '../../../components/ui/UiBadge.vue';
import UiBarChart from '../../../components/ui/UiBarChart.vue';
import UiSplitBar from '../../../components/ui/UiSplitBar.vue';
import UiTable from '../../../components/ui/UiTable.vue';
import UiTableRow from '../../../components/ui/UiTableRow.vue';
import UiTableCell from '../../../components/ui/UiTableCell.vue';
import UiTableHeader from '../../../components/ui/UiTableHeader.vue';
import type { UserSummary } from '../../../types/admin';
import { formatDate } from '../../../support/date';

const props = defineProps<{
    user: UserSummary;
    activity: { label: string; value: number }[];
    memoryMix: { label: string; value: number; color: string }[];
    contributions: { id: number; name: string; written: number }[];
    recent: {
        id: number;
        child: { id: number; name: string } | null;
        milestone: string | null;
        description: string | null;
        date: string;
        mood: string | null;
        media: number;
    }[];
}>();

const busiest = computed(() => Math.max(1, ...props.contributions.map((child) => child.written)));

const facts = computed(() => [
    { label: 'Status', value: props.user.is_registered ? 'Signed up' : 'Guest account' },
    { label: 'Share code', value: props.user.share_code },
    { label: 'Joined', value: formatDate(props.user.created_at) },
    { label: 'Last memory', value: formatDate(props.user.last_entry_date) },
    { label: 'Photos uploaded', value: String(props.user.photos) },
    { label: 'Language', value: props.user.language ?? '—' },
    { label: 'Timezone', value: props.user.timezone },
    { label: 'Devices', value: String(props.user.devices_count) },
]);
</script>

<template>
    <Head :title="user.name" />

    <UserShell :user="user" tab="overview">
        <div class="mb-4 grid gap-4 lg:grid-cols-3">
            <UiCard title="Memories captured" class="lg:col-span-2">
                <p class="mb-4 text-body text-slate-500">
                    Every memory this account wrote, by the week they sat down and wrote it.
                </p>
                <UiBarChart :points="activity" :label-every="3" />
            </UiCard>

            <UiCard title="Account">
                <div class="flex flex-col gap-2.5">
                    <div v-for="fact in facts" :key="fact.label" class="flex items-baseline justify-between gap-3">
                        <span class="text-body text-slate-500">{{ fact.label }}</span>
                        <span class="text-body font-medium text-slate-800">{{ fact.value }}</span>
                    </div>
                </div>
            </UiCard>
        </div>

        <div class="mb-4 grid gap-4 lg:grid-cols-2">
            <UiCard title="Where their memories come from">
                <p class="mb-4 text-body text-slate-500">
                    A milestone memory is worth its own XP; a free one is capped at one a day.
                </p>
                <UiSplitBar :slices="memoryMix" />
            </UiCard>

            <UiCard title="Who they write about">
                <p class="mb-4 text-body text-slate-500">Memories written by this account, per child.</p>

                <div v-if="contributions.length" class="flex flex-col gap-3">
                    <div v-for="child in contributions" :key="child.id">
                        <div class="mb-1 flex items-baseline justify-between gap-3 text-body">
                            <span class="font-medium text-slate-800">{{ child.name }}</span>
                            <span class="text-slate-400">{{ child.written }}</span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full bg-primary/70"
                                :style="{ width: `${(child.written / busiest) * 100}%` }"
                            />
                        </div>
                    </div>
                </div>
                <p v-else class="text-body text-slate-400">No children on this account yet.</p>
            </UiCard>
        </div>

        <UiCard title="Latest memories" flush>
            <UiTable
                bare
                :empty="recent.length === 0"
                empty-title="Nothing written yet"
                :empty-description="`${user.name} has not recorded a memory.`"
            >
                <template #header>
                    <UiTableHeader>Date</UiTableHeader>
                    <UiTableHeader>Child</UiTableHeader>
                    <UiTableHeader>Milestone</UiTableHeader>
                    <UiTableHeader>Story</UiTableHeader>
                    <UiTableHeader align="right">Media</UiTableHeader>
                </template>
                <template #body>
                    <UiTableRow v-for="entry in recent" :key="entry.id">
                        <UiTableCell cell-class="whitespace-nowrap text-slate-500">
                            {{ formatDate(entry.date) }}
                        </UiTableCell>
                        <UiTableCell>
                            <Link
                                v-if="entry.child"
                                :href="`/admin/children/${entry.child.id}`"
                                class="font-medium text-slate-900 hover:underline"
                            >
                                {{ entry.child.name }}
                            </Link>
                        </UiTableCell>
                        <UiTableCell>
                            <span v-if="entry.milestone" class="text-slate-800">{{ entry.milestone }}</span>
                            <UiBadge v-else tone="neutral">free</UiBadge>
                        </UiTableCell>
                        <UiTableCell cell-class="max-w-md">
                            <span class="text-slate-700">{{ entry.description ?? '—' }}</span>
                            <UiBadge v-if="entry.mood" tone="primary" class="ml-2">{{ entry.mood }}</UiBadge>
                        </UiTableCell>
                        <UiTableCell align="right">{{ entry.media || '—' }}</UiTableCell>
                    </UiTableRow>
                </template>
            </UiTable>
        </UiCard>
    </UserShell>
</template>
