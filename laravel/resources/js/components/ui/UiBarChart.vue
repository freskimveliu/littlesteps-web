<script setup lang="ts">
import { computed } from 'vue';

interface Point {
    label: string;
    value: number;
}

const props = withDefaults(
    defineProps<{
        points: Point[];
        height?: number;
        tone?: 'primary' | 'butter';
        /** Show every nth label, so a 12-week axis does not turn into mush. */
        labelEvery?: number;
    }>(),
    { height: 120, tone: 'primary', labelEvery: 1 },
);

const max = computed(() => Math.max(1, ...props.points.map((p) => p.value)));
const total = computed(() => props.points.reduce((sum, p) => sum + p.value, 0));
</script>

<template>
    <div>
        <div class="flex items-end gap-1" :style="{ height: `${height}px` }">
            <div
                v-for="(point, i) in points"
                :key="i"
                class="group relative flex flex-1 items-end justify-center"
                :style="{ height: '100%' }"
            >
                <div
                    class="w-full rounded-t-[3px] transition-[height] duration-300 ease-out"
                    :class="tone === 'primary' ? 'bg-primary/70 group-hover:bg-primary' : 'bg-butter group-hover:brightness-95'"
                    :style="{ height: `${Math.max(point.value === 0 ? 2 : 6, (point.value / max) * height)}px` }"
                />

                <span
                    class="pointer-events-none absolute -top-7 z-10 hidden whitespace-nowrap rounded-ui bg-secondary px-2 py-1 text-label text-white shadow-lg group-hover:block"
                >
                    {{ point.label }}: {{ point.value }}
                </span>
            </div>
        </div>

        <div class="mt-2 flex gap-1">
            <span
                v-for="(point, i) in points"
                :key="i"
                class="flex-1 text-center text-label text-slate-400"
            >
                {{ i % labelEvery === 0 ? point.label : '' }}
            </span>
        </div>

        <p v-if="total === 0" class="mt-1 text-label text-slate-400">Nothing recorded in this window yet.</p>
    </div>
</template>
