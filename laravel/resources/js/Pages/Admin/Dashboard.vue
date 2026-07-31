<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '../../layouts/AdminLayout.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiSectionLabel from '../../components/ui/UiSectionLabel.vue';

defineProps<{
    catalogue: { label: string; value: number; href: string }[];
    usage: { label: string; value: number; href: string | null }[];
    gifts: { id: number; name: string; reward: string; metric: string; threshold: number }[];
}>();

const rewardTone = { story: 'primary', image: 'success', book: 'gold' } as const;
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout>
        <UiPageHeader title="Dashboard" subtitle="What the catalogue holds, and what families have done with it." />

        <UiSectionLabel>Catalogue</UiSectionLabel>
        <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
            <Link v-for="stat in catalogue" :key="stat.label" :href="stat.href">
                <UiCard body-class="px-5 py-4">
                    <p class="text-2xl font-bold text-slate-900">{{ stat.value }}</p>
                    <p class="text-body text-slate-500">{{ stat.label }}</p>
                </UiCard>
            </Link>
        </div>

        <UiSectionLabel>Families</UiSectionLabel>
        <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
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

        <UiSectionLabel>Badges that carry a gift</UiSectionLabel>
        <UiCard>
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
    </AdminLayout>
</template>
