<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    size?: 'xs' | 'sm' | 'md' | 'lg';
    tone?: 'primary' | 'secondary' | 'muted' | 'inverse';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'sm',
    tone: 'primary',
});

const sizeClass = computed(
    () =>
        ({
            xs: 'w-3.5 h-3.5 border-2',
            sm: 'w-[1.125rem] h-[1.125rem] border-2',
            md: 'w-6 h-6 border-[2.5px]',
            lg: 'w-8 h-8 border-[3px]',
        })[props.size],
);

const toneClass = computed(
    () =>
        ({
            primary: 'border-primary/20 border-t-[var(--color-primary)]',
            secondary: 'border-secondary/[0.18] border-t-[var(--color-secondary)]',
            muted: 'border-slate-500/[0.22] border-t-slate-500',
            inverse: 'border-white/35 border-t-white',
        })[props.tone],
);
</script>

<template>
    <span
        class="inline-block animate-spin rounded-full border-solid [animation-duration:0.7s]"
        :class="[sizeClass, toneClass]"
        aria-hidden="true"
    />
</template>
