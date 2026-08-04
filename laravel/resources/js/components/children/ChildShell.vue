<script setup lang="ts">
import { computed } from 'vue';
import AdminLayout from '../../layouts/AdminLayout.vue';
import UiPageHeader from '../ui/UiPageHeader.vue';
import UiCard from '../ui/UiCard.vue';
import UiBadge from '../ui/UiBadge.vue';
import UiButton from '../ui/UiButton.vue';
import UiAvatar from '../ui/UiAvatar.vue';
import type { ChildSummary, ChildTab } from '../../types/admin';
import { formatDate } from '../../support/date';

const props = defineProps<{ summary: ChildSummary; tab: ChildTab }>();

const child = computed(() => props.summary.child);
const level = computed(() => props.summary.level);

const tabs = computed(() => {
    const base = `/admin/children/${child.value.id}`;

    return [
        { key: 'journey', label: 'Journey', href: base },
        { key: 'memories', label: `Memories (${props.summary.entriesTotal})`, href: `${base}/memories` },
        { key: 'trophies', label: 'Trophies', href: `${base}/trophies` },
        { key: 'gifts', label: `Gifts (${props.summary.rewardsCount})`, href: `${base}/gifts` },
        { key: 'family', label: `Family (${props.summary.membersCount})`, href: `${base}/family` },
    ];
});

function age(months: number): string {
    if (months < 24) return `${months} months`;
    return `${Math.floor(months / 12)} yr ${months % 12} mo`;
}
</script>

<template>
    <AdminLayout>
        <UiPageHeader :title="child.name" back-to="/admin/children" back-label="Children">
            <template #actions>
                <UiBadge tone="primary">
                    Level {{ level.level }} of {{ summary.levelCount }} · {{ level.name }}
                </UiBadge>
            </template>
        </UiPageHeader>

        <UiCard class="mb-4" body-class="px-6 py-5">
            <div class="flex flex-wrap items-center gap-5">
                <UiAvatar :src="child.photo" :name="child.name" size="lg" ring />

                <div class="min-w-48 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-lg font-bold text-slate-900">{{ child.name }}</p>
                        <UiBadge tone="neutral" class="capitalize">{{ child.gender }}</UiBadge>
                    </div>
                    <p class="text-body text-slate-500">
                        {{ age(child.age_months) }} · born {{ formatDate(child.birthday) }}
                    </p>
                    <p v-if="child.creator" class="text-body text-slate-500">Added by {{ child.creator.name }}</p>
                </div>

                <div class="w-full sm:w-64">
                    <div class="mb-1.5 flex items-baseline justify-between gap-2">
                        <span class="text-body font-medium text-slate-900">{{ level.name }}</span>
                        <span class="text-body text-slate-500">
                            {{ child.xp }}<template v-if="level.next"> / {{ level.next.minXp }}</template> XP
                        </span>
                    </div>
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-primary"
                            :style="{ width: `${Math.round(level.progress * 100)}%` }"
                        />
                    </div>
                    <p class="mt-1.5 text-body text-slate-500">
                        <template v-if="level.next">{{ level.xpToNext }} XP to {{ level.next.name }}</template>
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
                <p class="text-2xl font-bold text-slate-900">{{ summary.entriesTotal }}</p>
                <p class="text-body text-slate-500">Memories</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ summary.metrics.days }}</p>
                <p class="text-body text-slate-500">Days</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ summary.metrics.streak }}</p>
                <p class="text-body text-slate-500">Streak</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ summary.metrics.chapters }} / 8</p>
                <p class="text-body text-slate-500">Chapters</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ age(child.age_months) }}</p>
                <p class="text-body text-slate-500">Born {{ formatDate(child.birthday) }}</p>
            </UiCard>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <UiButton v-for="t in tabs" :key="t.key" variant="outline" :active="tab === t.key" :to="t.href">
                {{ t.label }}
            </UiButton>
        </div>

        <slot />
    </AdminLayout>
</template>
