<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ChevronRightIcon from '@heroicons/vue/24/outline/esm/ChevronRightIcon.js';
import CheckCircleIcon from '@heroicons/vue/24/solid/esm/CheckCircleIcon.js';
import LockClosedIcon from '@heroicons/vue/24/outline/esm/LockClosedIcon.js';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import UiPageHeader from '../../../components/ui/UiPageHeader.vue';
import UiCard from '../../../components/ui/UiCard.vue';
import UiButton from '../../../components/ui/UiButton.vue';
import UiBadge from '../../../components/ui/UiBadge.vue';
import UiTable from '../../../components/ui/UiTable.vue';
import UiTableRow from '../../../components/ui/UiTableRow.vue';
import UiTableCell from '../../../components/ui/UiTableCell.vue';
import UiTableHeader from '../../../components/ui/UiTableHeader.vue';
import UiModal from '../../../components/ui/UiModal.vue';
import UiSectionLabel from '../../../components/ui/UiSectionLabel.vue';
import { formatDate, formatDateTime } from '../../../support/date';

type Media = {
    id: number;
    name: string;
    mime: string;
    size: number;
    thumb: string;
    display: string;
    original: string;
};

type Entry = {
    id: number;
    milestone: string | null;
    chapter: string | null;
    description: string | null;
    date: string;
    mood: string | null;
    is_free: boolean;
    author: { id: number; name: string; email: string | null } | null;
    media: Media[];
    properties: { label: string; value: string | null; unit: string | null }[];
    created_at: string | null;
    updated_at: string | null;
};

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
    level: {
        level: number;
        name: string;
        min_xp: number;
        next: { level: number; name: string; min_xp: number } | null;
        xp_to_next: number | null;
        progress: number;
    };
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
    rewards: {
        id: number;
        type: string;
        status: string;
        trophy: string | null;
        claimed_at: string | null;
        generated_at: string | null;
        has_content: boolean;
    }[];
    entries: Entry[];
    entriesTotal: number;
}>();

const tab = ref<'journey' | 'memories' | 'trophies' | 'gifts' | 'family'>('journey');

const tabs = [
    { key: 'journey', label: 'Journey' },
    { key: 'memories', label: `Memories (${props.entriesTotal})` },
    { key: 'trophies', label: 'Trophies' },
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

const expanded = ref<number | null>(null);

function toggleChapter(id: number) {
    expanded.value = expanded.value === id ? null : id;
}

const viewing = ref<Entry | null>(null);
const preview = ref<Media | null>(null);
const viewerOpen = ref(false);

function openEntry(entry: Entry) {
    viewing.value = entry;
    preview.value = entry.media[0] ?? null;
    viewerOpen.value = true;
}

function fileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
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

        <UiCard class="mb-4" body-class="px-6 py-5">
            <div class="flex flex-wrap items-center gap-5">
                <img
                    v-if="child.photo"
                    :src="child.photo"
                    :alt="child.name"
                    class="h-16 w-16 rounded-full object-cover ring-2 ring-primary/20"
                />
                <div
                    v-else
                    class="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-xl font-bold text-primary-accessible"
                >
                    {{ child.name.charAt(0).toUpperCase() }}
                </div>

                <div class="min-w-48 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-lg font-bold text-slate-900">{{ child.name }}</p>
                        <UiBadge tone="neutral" class="capitalize">{{ child.gender }}</UiBadge>
                    </div>
                    <p class="text-body text-slate-500">
                        {{ age(child.age_months) }} · born {{ formatDate(child.birthday) }}
                    </p>
                    <p v-if="child.creator" class="text-body text-slate-500">
                        Added by {{ child.creator.name }}
                    </p>
                </div>

                <div class="w-full sm:w-64">
                    <div class="mb-1.5 flex items-baseline justify-between gap-2">
                        <span class="text-body font-medium text-slate-900">{{ level.name }}</span>
                        <span class="text-body text-slate-500">
                            {{ child.xp }}<template v-if="level.next"> / {{ level.next.min_xp }}</template> XP
                        </span>
                    </div>
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-primary"
                            :style="{ width: `${Math.round(level.progress * 100)}%` }"
                        />
                    </div>
                    <p class="mt-1.5 text-body text-slate-500">
                        <template v-if="level.next">
                            {{ level.xp_to_next }} XP to {{ level.next.name }}
                        </template>
                        <template v-else>Top of the ladder</template>
                    </p>
                </div>
            </div>
        </UiCard>

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
                <p class="text-body text-slate-500">Born {{ formatDate(child.birthday) }}</p>
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
                <UiTableHeader align="right">Media</UiTableHeader>
            </template>
            <template #body>
                <UiTableRow
                    v-for="entry in entries"
                    :key="entry.id"
                    class="cursor-pointer"
                    @click="openEntry(entry)"
                >
                    <UiTableCell cell-class="whitespace-nowrap text-slate-500">
                        {{ formatDate(entry.date) }}
                    </UiTableCell>
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
                    <UiTableCell align="right">
                        <div class="flex items-center justify-end gap-2">
                            <div v-if="entry.media.length" class="flex -space-x-2">
                                <img
                                    v-for="media in entry.media.slice(0, 3)"
                                    :key="media.id"
                                    :src="media.thumb"
                                    alt=""
                                    class="h-8 w-8 rounded-ui object-cover ring-2 ring-white"
                                />
                                <span
                                    v-if="entry.media.length > 3"
                                    class="flex h-8 w-8 items-center justify-center rounded-ui bg-slate-100 text-label font-medium text-slate-500 ring-2 ring-white"
                                >
                                    +{{ entry.media.length - 3 }}
                                </span>
                            </div>
                            <span v-else class="text-slate-300">—</span>
                            <ChevronRightIcon class="h-3.5 w-3.5 text-slate-300" />
                        </div>
                    </UiTableCell>
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

        <UiTable v-else-if="tab === 'trophies'" :empty="trophies.length === 0" empty-title="No trophies configured">
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

        <UiTable
            v-else-if="tab === 'gifts'"
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

        <UiModal v-model="viewerOpen" size="xl">
            <template #header>
                <div>
                    <h3 class="text-card-title font-bold text-slate-900">
                        {{ viewing?.milestone ?? 'Free memory' }}
                    </h3>
                    <p class="text-label text-slate-400">
                        {{ formatDate(viewing?.date) }}
                        <span v-if="viewing?.chapter"> · {{ viewing.chapter }}</span>
                    </p>
                </div>
            </template>

            <div v-if="viewing" class="flex flex-col gap-6">
                <div v-if="preview">
                    <div class="overflow-hidden rounded-[14px] bg-slate-50">
                        <img :src="preview.display" alt="" class="max-h-[380px] w-full object-contain" />
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <div v-if="viewing.media.length > 1" class="flex flex-wrap gap-2">
                            <button
                                v-for="media in viewing.media"
                                :key="media.id"
                                type="button"
                                class="h-12 w-12 overflow-hidden rounded-ui ring-2 transition-colors"
                                :class="preview?.id === media.id ? 'ring-primary' : 'ring-transparent hover:ring-slate-200'"
                                @click="preview = media"
                            >
                                <img :src="media.thumb" alt="" class="h-full w-full object-cover" />
                            </button>
                        </div>
                        <a
                            :href="preview.original"
                            target="_blank"
                            rel="noopener"
                            class="ml-auto text-label text-slate-400 hover:text-primary-accessible hover:underline"
                        >
                            {{ preview.name }} · {{ fileSize(preview.size) }} · open original
                        </a>
                    </div>
                </div>
                <p v-else class="rounded-[14px] bg-slate-50 px-4 py-6 text-center text-body text-slate-400">
                    Nothing attached to this memory.
                </p>

                <div>
                    <UiSectionLabel>Story</UiSectionLabel>
                    <p class="text-body whitespace-pre-line text-slate-700">
                        {{ viewing.description || 'Nothing written.' }}
                    </p>
                    <UiBadge v-if="viewing.mood" tone="primary" class="mt-2">{{ viewing.mood }}</UiBadge>
                </div>

                <div v-if="viewing.properties.length">
                    <UiSectionLabel>Measured</UiSectionLabel>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div v-for="(p, i) in viewing.properties" :key="i" class="rounded-ui bg-slate-50 px-3 py-2">
                            <p class="text-label text-slate-400">{{ p.label }}</p>
                            <p class="text-body font-medium text-slate-800">{{ p.value }}{{ p.unit ?? '' }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <UiSectionLabel>Details</UiSectionLabel>
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3">
                        <div>
                            <dt class="text-label text-slate-400">Written by</dt>
                            <dd class="text-body text-slate-800">
                                <Link
                                    v-if="viewing.author"
                                    :href="`/admin/users/${viewing.author.id}`"
                                    class="hover:underline"
                                >
                                    {{ viewing.author.name }}
                                </Link>
                                <span v-else class="text-slate-300">—</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-label text-slate-400">Milestone</dt>
                            <dd class="text-body text-slate-800">{{ viewing.milestone ?? 'Free memory' }}</dd>
                        </div>
                        <div>
                            <dt class="text-label text-slate-400">Chapter</dt>
                            <dd class="text-body text-slate-800">{{ viewing.chapter ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-label text-slate-400">Recorded</dt>
                            <dd class="text-body text-slate-800">{{ formatDateTime(viewing.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-label text-slate-400">Last edited</dt>
                            <dd class="text-body text-slate-800">{{ formatDateTime(viewing.updated_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-label text-slate-400">Photos</dt>
                            <dd class="text-body text-slate-800">{{ viewing.media.length }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </UiModal>
    </AdminLayout>
</template>
