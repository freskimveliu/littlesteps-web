<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ChevronLeftIcon from '@heroicons/vue/24/outline/esm/ChevronLeftIcon.js';

defineProps<{
    title: string;
    subtitle?: string;
    backTo?: string;
    backLabel?: string;
}>();
</script>

<template>
    <div class="mb-6">
        <Link
            v-if="backTo"
            :href="backTo"
            class="mb-2 inline-flex items-center text-body text-slate-500 transition-colors hover:text-primary-accessible"
        >
            <ChevronLeftIcon class="mr-1 h-3.5 w-3.5" />
            {{ backLabel ?? 'Back' }}
        </Link>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900">{{ title }}</h1>
                <p v-if="subtitle" class="mt-1 text-body text-slate-500">{{ subtitle }}</p>
            </div>
            <div class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
