<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        src?: string | null;
        name: string;
        size?: 'sm' | 'md' | 'lg';
        ring?: boolean;
    }>(),
    { src: null, size: 'md', ring: false },
);

const sizeClasses: Record<'sm' | 'md' | 'lg', string> = {
    sm: 'h-9 w-9 text-body',
    md: 'h-12 w-12 text-lg',
    lg: 'h-16 w-16 text-xl',
};

const classes = computed(() => [sizeClasses[props.size], props.ring ? 'ring-2 ring-primary/20' : '']);

const initial = computed(() => props.name.trim().charAt(0).toUpperCase());
</script>

<template>
    <img
        v-if="src"
        :src="src"
        :alt="name"
        class="shrink-0 rounded-full object-cover"
        :class="classes"
    />
    <span
        v-else
        class="flex shrink-0 items-center justify-center rounded-full bg-primary/10 font-bold text-primary-accessible"
        :class="classes"
    >
        {{ initial }}
    </span>
</template>
