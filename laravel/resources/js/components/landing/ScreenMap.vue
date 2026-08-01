<script setup lang="ts">
import Ion from './Ion.vue';
import PhoneTabBar from './PhoneTabBar.vue';
import type { IconName } from './icons';

interface Node {
    x: number;
    y: number;
    title: string;
    icon: IconName;
    state: 'done' | 'current' | 'locked';
    index: number;
    color: string;
}

/* Same winding layout the app draws: x alternates 22% / 50% / 78% of the width. */
const NODES: Node[] = [
    { x: 63, y: 42, title: 'Pretend Play', icon: 'color-palette', state: 'done', index: 1, color: '#8A5CD1' },
    { x: 144, y: 128, title: 'Favorite Song', icon: 'musical-notes', state: 'done', index: 2, color: '#8A5CD1' },
    { x: 225, y: 214, title: 'First Show of Empathy', icon: 'cloud', state: 'current', index: 3, color: '#2F91C4' },
    { x: 144, y: 300, title: 'Their Fears', icon: 'moon', state: 'locked', index: 4, color: '#2F91C4' },
    { x: 63, y: 386, title: 'First Friend', icon: 'people', state: 'locked', index: 5, color: '#C08400' },
];

const PATH =
    'M 63 42 C 63 82, 144 88, 144 128 C 144 168, 225 174, 225 214 C 225 254, 144 260, 144 300 C 144 340, 63 346, 63 386';

const CHAPTERS = [
    { name: 'The First Hello', count: '15/15', active: false, locked: false },
    { name: 'On the Move', count: '23/23', active: false, locked: false },
    { name: 'Words and Wonder', count: '8/14', active: true, locked: false },
    { name: 'A Mind of Their Own', count: '', active: false, locked: true },
];
</script>

<template>
    <div class="absolute inset-0 top-9 overflow-hidden bg-white">
        <div class="px-5 pt-2">
            <h3 class="font-display text-[22px] leading-tight font-extrabold text-gray-900">Adventure Map</h3>
            <div class="mt-1 flex items-center gap-1.5">
                <span class="flex h-4 w-4 items-center justify-center rounded-full bg-primary">
                    <Ion name="checkmark" :size="9" class="text-white" />
                </span>
                <span class="text-[11px] text-gray-500">34 of 48 steps completed</span>
            </div>
        </div>

        <div class="mt-3 flex gap-2 overflow-hidden px-5">
            <span
                v-for="chapter in CHAPTERS"
                :key="chapter.name"
                class="flex shrink-0 items-center gap-1.5 rounded-2xl px-3 py-2 text-[11px] font-bold whitespace-nowrap"
                :class="
                    chapter.active
                        ? 'bg-primary text-white'
                        : chapter.locked
                          ? 'bg-gray-100 text-gray-400'
                          : 'bg-primary/10 text-primary'
                "
            >
                <Ion v-if="chapter.locked" name="lock-closed" :size="10" />
                {{ chapter.name }}
                <span v-if="chapter.count" :class="chapter.active ? 'text-white/70' : 'text-primary/60'">
                    {{ chapter.count }}
                </span>
            </span>
        </div>

        <div class="mt-3 px-5">
            <div class="flex items-center justify-center gap-1.5 rounded-2xl bg-primary/10 py-2.5">
                <Ion name="add-circle-outline" :size="15" class="text-primary" />
                <span class="text-[11px] font-bold text-primary">Add a step to Words and Wonder</span>
            </div>
        </div>

        <!-- winding path -->
        <div class="relative mt-2 h-[430px] w-full">
            <svg viewBox="0 0 288 430" class="absolute inset-0 h-full w-full" style="left: 16px; width: 288px">
                <defs>
                    <linearGradient id="ls-path" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#A98FD6" />
                        <stop offset="100%" stop-color="#D9CBEF" />
                    </linearGradient>
                </defs>
                <path :d="PATH" stroke="url(#ls-path)" stroke-width="14" stroke-linecap="round" fill="none" />
                <path
                    :d="PATH"
                    stroke="#fff"
                    stroke-width="2.5"
                    stroke-dasharray="5 10"
                    stroke-linecap="round"
                    fill="none"
                    opacity="0.55"
                />
                <circle cx="30" cy="120" r="4" fill="#FFE5A0" />
                <circle cx="255" cy="80" r="3" fill="#7E5EBF" opacity="0.25" />
                <circle cx="40" cy="300" r="3" fill="#7E5EBF" opacity="0.2" />
                <circle cx="250" cy="350" r="4.5" fill="#FFE5A0" opacity="0.8" />
                <path
                    d="M 246 296 c 0-3 4-6 8-2 4-4 8 0 8 3 0 5-8 10-8 10 s-8-5-8-11 z"
                    fill="#7E5EBF"
                    opacity="0.18"
                />
            </svg>

            <div
                v-for="node in NODES"
                :key="node.title"
                class="absolute flex flex-col items-center"
                :style="{ left: `${16 + node.x - 34}px`, top: `${node.y - 28}px`, width: '68px' }"
            >
                <span
                    class="relative flex h-14 w-14 items-center justify-center rounded-full border-[3px] border-white"
                    :class="[
                        node.state === 'locked' ? 'bg-gray-200' : 'shadow-lg',
                        node.state === 'current' ? 'pulse-ring' : '',
                    ]"
                    :style="
                        node.state === 'locked'
                            ? undefined
                            : {
                                  backgroundColor: node.state === 'done' ? '#7E5EBF' : node.color,
                                  boxShadow: `0 8px 18px -6px ${node.color}99`,
                              }
                    "
                >
                    <Ion v-if="node.state === 'done'" name="checkmark" :size="24" class="text-white" />
                    <Ion v-else-if="node.state === 'current'" :name="node.icon" :size="22" class="text-white" />
                    <span v-else class="font-display text-base font-extrabold text-gray-400">{{ node.index }}</span>
                </span>

                <span
                    class="mt-1.5 rounded-full px-2 py-0.5 text-center text-[9px] leading-tight font-bold"
                    :class="node.state === 'locked' ? 'text-gray-400' : 'text-gray-700'"
                >
                    {{ node.title }}
                </span>
            </div>
        </div>

        <PhoneTabBar active="milestones" />
    </div>
</template>
