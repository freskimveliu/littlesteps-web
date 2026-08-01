<script setup lang="ts">
import Ion from './Ion.vue';
import KidAvatar from './KidAvatar.vue';
import PhoneTabBar from './PhoneTabBar.vue';
import type { IconName } from './icons';

interface Badge {
    title: string;
    description: string;
    icon: IconName;
    xp: number;
    unlocked: boolean;
    current?: number;
    target?: number;
}

const BADGES: Badge[] = [
    { title: 'First Week', description: 'A memory on seven different days', icon: 'flame', xp: 60, unlocked: true },
    { title: 'First Chapter', description: 'You finished a whole chapter', icon: 'ribbon', xp: 200, unlocked: true },
    { title: 'Shutterbug', description: 'Twenty-five photos kept', icon: 'camera', xp: 100, unlocked: false, current: 18, target: 25 },
    { title: 'Hundred Days', description: 'One hundred days of memories', icon: 'calendar', xp: 250, unlocked: false, current: 62, target: 100 },
];

const RING = 2 * Math.PI * 34;
</script>

<template>
    <div class="absolute inset-0 top-9 overflow-hidden bg-gray-50">
        <!-- awards hero -->
        <div class="relative mx-4 mt-2 overflow-hidden rounded-3xl bg-linear-135 from-primary to-violet-soft p-4 pb-10">
            <div class="absolute -top-10 -right-8 h-28 w-28 rounded-full bg-white/10" />
            <div class="absolute -bottom-12 right-12 h-24 w-24 rounded-full bg-butter/10" />

            <div class="relative mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-white/25 p-0.5">
                        <KidAvatar :size="26" variant="a" />
                    </span>
                    <span class="text-[13px] font-bold text-white/80">Elena</span>
                </div>
                <span class="flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-1 text-[10px] font-extrabold text-white">
                    <Ion name="star" :size="11" class="text-butter" />
                    1,240 XP
                </span>
            </div>

            <div class="relative flex items-center">
                <div class="relative flex h-[78px] w-[78px] items-center justify-center">
                    <svg viewBox="0 0 78 78" class="absolute inset-0 h-full w-full -rotate-90">
                        <circle cx="39" cy="39" r="34" fill="none" stroke="rgba(255,255,255,0.25)" stroke-width="7" />
                        <circle
                            cx="39"
                            cy="39"
                            r="34"
                            fill="none"
                            stroke="#fff"
                            stroke-width="7"
                            stroke-linecap="round"
                            :stroke-dasharray="`${RING * 0.75} ${RING}`"
                        />
                    </svg>
                    <Ion name="albums" :size="26" class="relative text-white" />
                </div>

                <div class="ml-4 flex-1">
                    <p class="font-display text-xl leading-tight font-extrabold text-white">Moment Collector</p>
                    <p class="text-[12px] text-white/70">Level 4 of 14</p>
                    <p class="mt-0.5 text-[10px] font-bold text-white/70">160 XP to Rising Star</p>
                </div>
            </div>

            <div class="relative mt-4 flex gap-2">
                <div v-for="stat in [{ v: '9/32', l: 'Badges' }, { v: '12', l: 'Streak' }, { v: '62', l: 'Memories' }]" :key="stat.l" class="flex-1 rounded-2xl bg-white/15 py-2 text-center">
                    <p class="font-display text-sm leading-none font-extrabold text-white">{{ stat.v }}</p>
                    <p class="mt-1 text-[9px] text-white/60">{{ stat.l }}</p>
                </div>
            </div>
        </div>

        <!-- segmented control -->
        <div class="relative mx-8 -mt-7">
            <div class="flex gap-1 rounded-2xl bg-white p-1 shadow-lg shadow-primary/10">
                <span class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-primary py-2 text-[11px] font-extrabold text-white">
                    <Ion name="ribbon" :size="13" />
                    Badges
                    <span class="text-white/70">9/32</span>
                </span>
                <span class="flex flex-1 items-center justify-center gap-1.5 rounded-xl py-2 text-[11px] font-extrabold text-gray-400">
                    <Ion name="map" :size="13" />
                    Journey
                    <span class="text-gray-300">4/14</span>
                </span>
            </div>
        </div>

        <p class="mt-3 text-center text-[10px] text-gray-400">Keep capturing memories to unlock more</p>

        <!-- badge grid -->
        <div class="mt-2 grid grid-cols-2 gap-2 px-4">
            <div
                v-for="badge in BADGES"
                :key="badge.title"
                class="flex flex-col items-center rounded-3xl p-3"
                :class="badge.unlocked ? 'bg-primary/10' : 'bg-white'"
            >
                <span
                    class="relative flex h-12 w-12 items-center justify-center rounded-full"
                    :class="badge.unlocked ? 'bg-primary' : 'bg-gray-100'"
                >
                    <Ion :name="badge.icon" :size="21" :class="badge.unlocked ? 'text-white' : 'text-gray-400'" />
                    <span
                        v-if="!badge.unlocked"
                        class="absolute right-0 bottom-0 flex h-4 w-4 items-center justify-center rounded-full bg-white"
                    >
                        <Ion name="lock-closed" :size="8" class="text-gray-400" />
                    </span>
                </span>

                <p
                    class="mt-2 text-center font-display text-[12px] leading-tight font-extrabold"
                    :class="badge.unlocked ? 'text-gray-900' : 'text-gray-400'"
                >
                    {{ badge.title }}
                </p>
                <p class="mt-0.5 text-center text-[9px] leading-tight text-gray-400">{{ badge.description }}</p>

                <span
                    v-if="badge.unlocked"
                    class="mt-2 flex items-center gap-0.5 rounded-full bg-butter/60 px-2 py-0.5 text-[10px] font-extrabold text-primary"
                >
                    <Ion name="star" :size="9" />
                    +{{ badge.xp }}
                </span>
                <div v-else class="mt-2 w-full">
                    <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
                        <div
                            class="h-full rounded-full bg-primary/50"
                            :style="{ width: `${((badge.current ?? 0) / (badge.target ?? 1)) * 100}%` }"
                        />
                    </div>
                    <p class="mt-1 text-center text-[10px] font-extrabold text-gray-400">
                        {{ badge.current }}/{{ badge.target }}
                    </p>
                </div>
            </div>
        </div>

        <PhoneTabBar active="awards" />
    </div>
</template>
