<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ChevronRightIcon from '@heroicons/vue/24/outline/esm/ChevronRightIcon.js';
import ChildShell from '../../../../components/children/ChildShell.vue';
import UiBadge from '../../../../components/ui/UiBadge.vue';
import UiTable from '../../../../components/ui/UiTable.vue';
import UiTableRow from '../../../../components/ui/UiTableRow.vue';
import UiTableCell from '../../../../components/ui/UiTableCell.vue';
import UiTableHeader from '../../../../components/ui/UiTableHeader.vue';
import UiModal from '../../../../components/ui/UiModal.vue';
import UiSectionLabel from '../../../../components/ui/UiSectionLabel.vue';
import type { ChildSummary } from '../../../../types/admin';
import { formatDate, formatDateTime } from '../../../../support/date';

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

defineProps<{ summary: ChildSummary; entries: Entry[] }>();

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
    <Head :title="`${summary.child.name} · Memories`" />

    <ChildShell :summary="summary" tab="memories">
        <UiTable
            :empty="entries.length === 0"
            empty-title="No memories yet"
            :empty-description="`Nothing has been recorded for ${summary.child.name}.`"
        >
            <template #header>
                <UiTableHeader>Date</UiTableHeader>
                <UiTableHeader>Milestone</UiTableHeader>
                <UiTableHeader>Story</UiTableHeader>
                <UiTableHeader>Measured</UiTableHeader>
                <UiTableHeader align="right">Media</UiTableHeader>
            </template>
            <template #body>
                <UiTableRow v-for="entry in entries" :key="entry.id" class="cursor-pointer" @click="openEntry(entry)">
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
                    v-if="summary.entriesTotal > entries.length"
                    class="border-t border-[#f0f4f8] px-6 py-3 text-body text-slate-400"
                >
                    Showing the most recent {{ entries.length }} of {{ summary.entriesTotal }}.
                </p>
            </template>
        </UiTable>

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
                                :class="
                                    preview?.id === media.id ? 'ring-primary' : 'ring-transparent hover:ring-slate-200'
                                "
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
    </ChildShell>
</template>
