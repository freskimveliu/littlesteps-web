<script setup lang="ts">
import Ion from './Ion.vue';
import type { IconName } from './icons';

interface Props {
    active: 'home' | 'timeline' | 'milestones' | 'awards' | 'profile';
}

defineProps<Props>();

const TABS: { key: Props['active']; label: string; icon: IconName; outline: IconName }[] = [
    { key: 'home', label: 'Home', icon: 'home', outline: 'home-outline' },
    { key: 'timeline', label: 'Timeline', icon: 'time', outline: 'time-outline' },
    { key: 'milestones', label: 'Milestones', icon: 'star', outline: 'star-outline' },
    { key: 'awards', label: 'Awards', icon: 'trophy', outline: 'trophy-outline' },
    { key: 'profile', label: 'Profile', icon: 'person', outline: 'person-outline' },
];
</script>

<template>
    <div
        class="absolute inset-x-0 bottom-0 z-20 flex items-start justify-around border-t border-black/5 bg-[#f9f9fb]/95 pt-2 pb-6 backdrop-blur-sm"
    >
        <div v-for="tab in TABS" :key="tab.key" class="flex w-12 flex-col items-center gap-1">
            <Ion
                :name="active === tab.key ? tab.icon : tab.outline"
                :size="21"
                :class="active === tab.key ? 'text-primary' : 'text-[#8E8E93]'"
            />
            <span
                class="text-[9px] font-semibold"
                :class="active === tab.key ? 'text-primary' : 'text-[#8E8E93]'"
            >
                {{ tab.label }}
            </span>
        </div>

        <div class="absolute bottom-1.5 left-1/2 h-[4px] w-[110px] -translate-x-1/2 rounded-full bg-ink/80" />
    </div>
</template>
