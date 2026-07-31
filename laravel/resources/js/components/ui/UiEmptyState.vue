<script setup lang="ts">
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        icon?: Component;
        title?: string;
        description?: string;
        size?: 'sm' | 'md';
    }>(),
    { size: 'md' },
);
</script>

<template>
    <div
        class="flex flex-col items-center text-center text-slate-500"
        :class="size === 'sm' ? 'px-4 pt-8 pb-6' : 'px-6 pt-12 pb-8'"
    >
        <component :is="icon" v-if="icon" class="mb-4 h-10 w-10 text-slate-300" />
        <h4 v-if="title" class="mb-1.5 text-sm leading-[1.3] font-bold text-slate-900">{{ title }}</h4>
        <p v-if="description" class="m-0 max-w-xs text-body text-slate-500">{{ description }}</p>
        <div v-if="$slots.default" class="mt-4 flex flex-wrap justify-center gap-2">
            <slot />
        </div>
    </div>
</template>
