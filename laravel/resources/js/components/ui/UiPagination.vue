<script setup lang="ts">
import { computed } from 'vue';
import ChevronLeftIcon from '@heroicons/vue/24/outline/esm/ChevronLeftIcon.js';
import ChevronRightIcon from '@heroicons/vue/24/outline/esm/ChevronRightIcon.js';

const props = withDefaults(
    defineProps<{
        currentPage: number;
        lastPage: number;
        total?: number;
        perPage?: number;
        /** How many pages to show either side of the current one. */
        window?: number;
    }>(),
    { window: 1 },
);

const emit = defineEmits<{ change: [page: number] }>();

/**
 * 1 … 4 5 6 … 12 — always the first and last page, a window around the
 * current one, and an ellipsis wherever the run breaks.
 */
const pages = computed<(number | '…')[]>(() => {
    const { currentPage: current, lastPage: last, window: w } = props;

    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }

    const shown = new Set<number>([1, last]);
    for (let p = current - w; p <= current + w; p++) {
        if (p >= 1 && p <= last) shown.add(p);
    }

    const sorted = [...shown].sort((a, b) => a - b);
    const out: (number | '…')[] = [];

    sorted.forEach((page, i) => {
        if (i > 0 && page - sorted[i - 1] > 1) out.push('…');
        out.push(page);
    });

    return out;
});

const from = computed(() =>
    props.total && props.perPage ? (props.currentPage - 1) * props.perPage + 1 : null,
);
const to = computed(() =>
    props.total && props.perPage ? Math.min(props.currentPage * props.perPage, props.total) : null,
);

function go(page: number) {
    if (page < 1 || page > props.lastPage || page === props.currentPage) return;
    emit('change', page);
}
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#f0f4f8] px-6 py-4">
        <p class="text-body text-slate-500">
            <template v-if="from !== null">Showing {{ from }}–{{ to }} of {{ total }}</template>
            <template v-else>Page {{ currentPage }} of {{ lastPage }}</template>
        </p>

        <nav class="flex items-center gap-1">
            <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-ui border border-slate-200 text-slate-500 transition-colors hover:border-primary hover:text-primary-accessible disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:text-slate-500"
                :disabled="currentPage === 1"
                title="Previous"
                @click="go(currentPage - 1)"
            >
                <ChevronLeftIcon class="h-4 w-4" />
            </button>

            <template v-for="(page, i) in pages" :key="i">
                <span v-if="page === '…'" class="px-1 text-body text-slate-400">…</span>
                <button
                    v-else
                    type="button"
                    class="h-8 min-w-8 rounded-ui px-2 text-body font-medium transition-colors"
                    :class="
                        page === currentPage
                            ? 'bg-primary text-primary-text'
                            : 'border border-slate-200 text-slate-600 hover:border-primary hover:text-primary-accessible'
                    "
                    @click="go(page as number)"
                >
                    {{ page }}
                </button>
            </template>

            <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-ui border border-slate-200 text-slate-500 transition-colors hover:border-primary hover:text-primary-accessible disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:text-slate-500"
                :disabled="currentPage === lastPage"
                title="Next"
                @click="go(currentPage + 1)"
            >
                <ChevronRightIcon class="h-4 w-4" />
            </button>
        </nav>
    </div>
</template>
