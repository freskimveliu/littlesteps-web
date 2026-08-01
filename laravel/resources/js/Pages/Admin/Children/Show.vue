<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import UiPageHeader from '../../../components/ui/UiPageHeader.vue';
import UiCard from '../../../components/ui/UiCard.vue';
import UiButton from '../../../components/ui/UiButton.vue';
import UiBadge from '../../../components/ui/UiBadge.vue';
import UiTable from '../../../components/ui/UiTable.vue';
import UiTableRow from '../../../components/ui/UiTableRow.vue';
import UiTableCell from '../../../components/ui/UiTableCell.vue';
import UiTableHeader from '../../../components/ui/UiTableHeader.vue';

const props = defineProps<{
    child: {
        id: number;
        name: string;
        birthday: string;
        age_months: number;
        gender: string;
        xp: number;
        photo: string | null;
        created_at: string | null;
        creator: { id: number; name: string; email: string | null } | null;
    };
    level: { level: number; name: string; xp_to_next: number | null; progress: number };
    levelCount: number;
    metrics: Record<string, number>;
    members: {
        id: number;
        user: { id: number; name: string; email: string | null } | null;
        relation: string;
        role: string;
        is_creator: boolean;
    }[];
    chapters: {
        id: number;
        name: string;
        months_from: number | null;
        xp: number;
        is_hidden: boolean;
        completed_at: string | null;
        milestones_total: number;
        milestones_recorded: number;
    }[];
    badges: {
        id: number;
        name: string;
        metric: string;
        threshold: number;
        reward: string | null;
        progress: number;
        unlocked_at: string | null;
    }[];
    rewards: {
        id: number;
        type: string;
        status: string;
        badge: string | null;
        claimed_at: string | null;
        generated_at: string | null;
        has_content: boolean;
    }[];
    entries: {
        id: number;
        milestone: string | null;
        description: string | null;
        date: string;
        mood: string | null;
        is_free: boolean;
        photos: number;
        properties: { label: string; value: string | null; unit: string | null }[];
        created_at: string | null;
    }[];
    entriesTotal: number;
}>();

const tab = ref<'journey' | 'memories' | 'badges' | 'gifts' | 'family'>('journey');

const tabs = [
    { key: 'journey', label: 'Journey' },
    { key: 'memories', label: `Memories (${props.entriesTotal})` },
    { key: 'badges', label: 'Badges' },
    { key: 'gifts', label: `Gifts (${props.rewards.length})` },
    { key: 'family', label: `Family (${props.members.length})` },
] as const;

const statusTone: Record<string, 'neutral' | 'primary' | 'success' | 'danger'> = {
    unclaimed: 'neutral',
    generating: 'primary',
    ready: 'success',
    failed: 'danger',
};

function age(months: number): string {
    if (months < 24) return `${months} months`;
    return `${Math.floor(months / 12)} yr ${months % 12} mo`;
}

function resetGift(id: number) {
    router.post(`/admin/gifts/${id}/reset`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="child.name" />

    <AdminLayout>
        <UiPageHeader :title="child.name" back-to="/admin/children" back-label="Children">
            <template #actions>
                <UiBadge tone="primary">Level {{ level.level }} of {{ levelCount }} · {{ level.name }}</UiBadge>
            </template>
        </UiPageHeader>

        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-6">
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ child.xp }}</p>
                <p class="text-body text-slate-500">XP</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ entriesTotal }}</p>
                <p class="text-body text-slate-500">Memories</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ metrics.days }}</p>
                <p class="text-body text-slate-500">Days</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ metrics.streak }}</p>
                <p class="text-body text-slate-500">Streak</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ metrics.chapters }} / 8</p>
                <p class="text-body text-slate-500">Chapters</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ age(child.age_months) }}</p>
                <p class="text-body text-slate-500">Born {{ child.birthday }}</p>
            </UiCard>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <UiButton
                v-for="t in tabs"
                :key="t.key"
                variant="outline"
                :active="tab === t.key"
                @click="tab = t.key"
            >
                {{ t.label }}
            </UiButton>
        </div>

        <UiTable v-if="tab === 'journey'" :empty="chapters.length === 0" empty-title="Not provisioned">
            <template #header>
                <UiTableHeader>Chapter</UiTableHeader>
                <UiTableHeader align="right">Opens at</UiTableHeader>
                <UiTableHeader>Progress</UiTableHeader>
                <UiTableHeader align="right">Finished</UiTableHeader>
            </template>
            <template #body>
                <UiTableRow v-for="chapter in chapters" :key="chapter.id">
                    <UiTableCell>
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-slate-900">{{ chapter.name }}</span>
                            <UiBadge v-if="chapter.is_hidden" tone="neutral">hidden</UiBadge>
                        </div>
                    </UiTableCell>
                    <UiTableCell align="right" cell-class="text-slate-500">{{ chapter.months_from }} mo</UiTableCell>
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
                            {{ chapter.completed_at.slice(0, 10) }}
                        </UiBadge>
                        <span v-else class="text-slate-300">—</span>
                    </UiTableCell>
                </UiTableRow>
            </template>
        </UiTable>

        <UiTable
            v-else-if="tab === 'memories'"
            :empty="entries.length === 0"
            empty-title="No memories yet"
            :empty-description="`Nothing has been recorded for ${child.name}.`"
        >
            <template #header>
                <UiTableHeader>Date</UiTableHeader>
                <UiTableHeader>Milestone</UiTableHeader>
                <UiTableHeader>Story</UiTableHeader>
                <UiTableHeader>Measured</UiTableHeader>
                <UiTableHeader align="right">Photos</UiTableHeader>
            </template>
            <template #body>
                <UiTableRow v-for="entry in entries" :key="entry.id">
                    <UiTableCell cell-class="whitespace-nowrap text-slate-500">{{ entry.date }}</UiTableCell>
                    <UiTableCell>
                        <span v-if="entry.milestone" class="text-slate-800">{{ entry.milestone }}</span>
                        <UiBadge v-else tone="neutral">free</UiBadge>
                    </UiTableCell>
                    <UiTableCell cell-class="max-w-md">
                        <span class="text-slate-700">{{ entry.description ?? '—' }}</span>
                        <UiBadge v-if="entry.mood" tone="primary" class="ml-2">{{ entry.mood }}</UiBadge>
                    </UiTableCell>
                    <UiTableCell>
                        <div class="flex flex-wrap gap-1">
                            <UiBadge v-for="(p, i) in entry.properties" :key="i" tone="neutral">
                                {{ p.label }}: {{ p.value }}{{ p.unit ?? '' }}
                            </UiBadge>
                        </div>
                    </UiTableCell>
                    <UiTableCell align="right">{{ entry.photos || '—' }}</UiTableCell>
                </UiTableRow>
            </template>
            <template #footer>
                <p
                    v-if="entriesTotal > entries.length"
                    class="border-t border-[#f0f4f8] px-6 py-3 text-body text-slate-400"
                >
                    Showing the most recent {{ entries.length }} of {{ entriesTotal }}.
                </p>
            </template>
        </UiTable>

        <UiTable v-else-if="tab === 'badges'" :empty="badges.length === 0" empty-title="No badges configured">
            <template #header>
                <UiTableHeader>Badge</UiTableHeader>
                <UiTableHeader>Rule</UiTableHeader>
                <UiTableHeader>Progress</UiTableHeader>
                <UiTableHeader>Gift</UiTableHeader>
                <UiTableHeader align="right">Earned</UiTableHeader>
            </template>
            <template #body>
                <UiTableRow v-for="badge in badges" :key="badge.id">
                    <UiTableCell cell-class="font-medium text-slate-900">{{ badge.name }}</UiTableCell>
                    <UiTableCell cell-class="text-slate-500">{{ badge.metric }} ≥ {{ badge.threshold }}</UiTableCell>
                    <UiTableCell>
                        <div class="flex items-center gap-2">
                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full"
                                    :class="badge.unlocked_at ? 'bg-emerald-500' : 'bg-primary'"
                                    :style="{ width: `${(badge.progress / badge.threshold) * 100}%` }"
                                />
                            </div>
                            <span class="text-slate-500">{{ badge.progress }} / {{ badge.threshold }}</span>
                        </div>
                    </UiTableCell>
                    <UiTableCell>
                        <UiBadge v-if="badge.reward" tone="gold">{{ badge.reward }}</UiBadge>
                        <span v-else class="text-slate-300">—</span>
                    </UiTableCell>
                    <UiTableCell align="right">
                        <UiBadge v-if="badge.unlocked_at" tone="success">{{ badge.unlocked_at.slice(0, 10) }}</UiBadge>
                        <span v-else class="text-slate-300">—</span>
                    </UiTableCell>
                </UiTableRow>
            </template>
        </UiTable>

        <UiTable
            v-else-if="tab === 'gifts'"
            :empty="rewards.length === 0"
            empty-title="No gifts earned yet"
            empty-description="A gift is reserved when a badge that carries one is unlocked."
        >
            <template #header>
                <UiTableHeader>Badge</UiTableHeader>
                <UiTableHeader>Type</UiTableHeader>
                <UiTableHeader>Status</UiTableHeader>
                <UiTableHeader align="right">Claimed</UiTableHeader>
                <UiTableHeader align="right" />
            </template>
            <template #body>
                <UiTableRow v-for="reward in rewards" :key="reward.id">
                    <UiTableCell cell-class="font-medium text-slate-900">{{ reward.badge }}</UiTableCell>
                    <UiTableCell>{{ reward.type }}</UiTableCell>
                    <UiTableCell>
                        <UiBadge :tone="statusTone[reward.status] ?? 'neutral'">{{ reward.status }}</UiBadge>
                    </UiTableCell>
                    <UiTableCell align="right" cell-class="text-slate-500">
                        {{ reward.claimed_at?.slice(0, 10) ?? '—' }}
                    </UiTableCell>
                    <UiTableCell align="right">
                        <UiButton
                            v-if="reward.status !== 'unclaimed'"
                            variant="outline"
                            @click="resetGift(reward.id)"
                        >
                            Reset
                        </UiButton>
                    </UiTableCell>
                </UiTableRow>
            </template>
        </UiTable>

        <UiCard v-else title="Who can see this child" flush>
            <UiTable bare :empty="members.length === 0" empty-title="No members">
                <template #header>
                    <UiTableHeader>Name</UiTableHeader>
                    <UiTableHeader>Relation</UiTableHeader>
                    <UiTableHeader>Role</UiTableHeader>
                    <UiTableHeader align="right" />
                </template>
                <template #body>
                    <UiTableRow v-for="member in members" :key="member.id">
                        <UiTableCell>
                            <Link
                                v-if="member.user"
                                :href="`/admin/users/${member.user.id}`"
                                class="font-medium text-slate-900 hover:underline"
                            >
                                {{ member.user.name }}
                            </Link>
                            <p class="text-label text-slate-400">{{ member.user?.email ?? 'not signed up' }}</p>
                        </UiTableCell>
                        <UiTableCell cell-class="text-slate-600">{{ member.relation }}</UiTableCell>
                        <UiTableCell>
                            <UiBadge :tone="member.is_creator ? 'primary' : 'neutral'">
                                {{ member.is_creator ? 'creator' : member.role }}
                            </UiBadge>
                        </UiTableCell>
                        <UiTableCell align="right">
                            <UiButton v-if="member.user" variant="outline" :to="`/admin/users/${member.user.id}`">
                                Open
                            </UiButton>
                        </UiTableCell>
                    </UiTableRow>
                </template>
            </UiTable>
        </UiCard>
    </AdminLayout>
</template>
