<script setup lang="ts">
import { computed } from 'vue';

interface Slice {
    label: string;
    value: number;
    color: string;
}

const props = defineProps<{ slices: Slice[] }>();

const total = computed(() => props.slices.reduce((sum, s) => sum + s.value, 0));
</script>

<template>
    <div>
        <div class="flex h-2.5 overflow-hidden rounded-full bg-slate-100">
            <div
                v-for="slice in slices"
                :key="slice.label"
                class="h-full first:rounded-l-full last:rounded-r-full"
                :style="{ width: `${total ? (slice.value / total) * 100 : 0}%`, backgroundColor: slice.color }"
                :title="`${slice.label}: ${slice.value}`"
            />
        </div>

        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1">
            <span v-for="slice in slices" :key="slice.label" class="flex items-center gap-1.5 text-body text-slate-600">
                <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: slice.color }" />
                {{ slice.label }}
                <span class="text-slate-400">{{ slice.value }}</span>
            </span>
        </div>

        <p v-if="total === 0" class="mt-2 text-label text-slate-400">No data yet.</p>
    </div>
</template>
