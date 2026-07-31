<script setup lang="ts">
import MagnifyingGlassIcon from '@heroicons/vue/24/outline/esm/MagnifyingGlassIcon.js';

withDefaults(defineProps<{ placeholder?: string }>(), { placeholder: 'Search…' });

const model = defineModel<string>({ default: '' });
</script>

<template>
    <div class="relative w-56">
        <MagnifyingGlassIcon class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input v-model="model" type="search" :placeholder="placeholder" class="ui-input pl-9" />
    </div>
</template>
