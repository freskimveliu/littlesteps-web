<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    tone?: 'neutral' | 'primary' | 'gold' | 'success' | 'danger';
}

const props = withDefaults(defineProps<Props>(), { tone: 'neutral' });

const toneClass = computed(
    () =>
        ({
            neutral: 'bg-slate-100 text-slate-600',
            primary: 'bg-primary/10 text-primary-accessible',
            gold: 'bg-amber-100 text-amber-800',
            success: 'bg-emerald-100 text-emerald-700',
            danger: 'bg-red-100 text-red-700',
        })[props.tone],
);
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2 py-0.5 text-label font-medium whitespace-nowrap"
        :class="toneClass"
    >
        <slot />
    </span>
</template>
