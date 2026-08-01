<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '../../layouts/AdminLayout.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiSectionLabel from '../../components/ui/UiSectionLabel.vue';
import UiBarChart from '../../components/ui/UiBarChart.vue';
import UiSplitBar from '../../components/ui/UiSplitBar.vue';

interface Point {
    label: string;
    value: number;
}

defineProps<{
    catalogue: { label: string; value: number; href: string }[];
    usage: { label: string; value: number; href: string | null }[];
    signups: Point[];
    memories: Point[];
    memoryMix: { label: string; value: number; color: string }[];
    giftStatus: { label: string; value: number; color: string }[];
    stepsPerMilestone: Point[];
    gifts: { id: number; name: string; reward: string; metric: string; threshold: number }[];
}>();

const rewardTone = { story: 'primary', image: 'success', book: 'gold' } as const;
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout>
        <UiPageHeader title="Dashboard" subtitle="What the catalogue holds, and what families have done with it." />

        <UiSectionLabel>Families</UiSectionLabel>
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <component
                :is="stat.href ? Link : 'div'"
                v-for="stat in usage"
                :key="stat.label"
                :href="stat.href ?? undefined"
            >
                <UiCard body-class="px-5 py-4">
                    <p class="text-2xl font-bold text-slate-900">{{ stat.value }}</p>
                    <p class="text-body text-slate-500">{{ stat.label }}</p>
                </UiCard>
            </component>
        </div>

        <div class="mb-6 grid gap-4 lg:grid-cols-2">
            <UiCard title="New accounts">
                <p class="mb-4 text-body text-slate-500">Sign-ups per week over the last twelve weeks.</p>
                <UiBarChart :points="signups" :label-every="3" />
            </UiCard>

            <UiCard title="Memories captured">
                <p class="mb-4 text-body text-slate-500">Every memory written, per week.</p>
                <UiBarChart :points="memories" :label-every="3" tone="butter" />
            </UiCard>
        </div>

        <div class="mb-8 grid gap-4 lg:grid-cols-2">
            <UiCard title="Where memories come from">
                <p class="mb-4 text-body text-slate-500">
                    A step memory is worth its own XP; a free one is capped at one a day.
                </p>
                <UiSplitBar :slices="memoryMix" />
            </UiCard>

            <UiCard title="Gifts">
                <p class="mb-4 text-body text-slate-500">
                    Anything sitting in <strong>generating</strong> is waiting on a job that does not exist yet.
                </p>
                <UiSplitBar :slices="giftStatus" />
            </UiCard>
        </div>

        <UiSectionLabel>Catalogue</UiSectionLabel>
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
            <Link v-for="stat in catalogue" :key="stat.label" :href="stat.href">
                <UiCard body-class="px-5 py-4">
                    <p class="text-2xl font-bold text-slate-900">{{ stat.value }}</p>
                    <p class="text-body text-slate-500">{{ stat.label }}</p>
                </UiCard>
            </Link>
        </div>

        <div class="mb-6 grid gap-4 lg:grid-cols-2">
            <UiCard title="Steps in each milestone">
                <p class="mb-4 text-body text-slate-500">
                    A milestone needs at least ten visible steps before a parent can finish it.
                </p>
                <UiBarChart :points="stepsPerMilestone" :height="100" />
            </UiCard>

            <UiCard title="Badges that carry a gift">
                <p class="mb-4 text-body text-slate-500">
                    Each of these costs a generation when a parent claims it. Nothing is generated until they do.
                </p>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="gift in gifts"
                        :key="gift.id"
                        class="flex items-center gap-2 rounded-ui border border-slate-200 px-3 py-2"
                    >
                        <span class="text-body font-medium text-slate-800">{{ gift.name }}</span>
                        <span class="text-label text-slate-400">{{ gift.metric }} ≥ {{ gift.threshold }}</span>
                        <UiBadge :tone="rewardTone[gift.reward as keyof typeof rewardTone] ?? 'neutral'">
                            {{ gift.reward }}
                        </UiBadge>
                    </div>
                </div>
            </UiCard>
        </div>
    </AdminLayout>
</template>
