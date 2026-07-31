<script setup lang="ts">
import InformationCircleIcon from '@heroicons/vue/24/outline/esm/InformationCircleIcon.js';

interface Props {
    forId?: string;
    label?: string;
    required?: boolean;
    error?: string;
    hint?: string;
}

withDefaults(defineProps<Props>(), { required: false });
</script>

<template>
    <div class="w-full">
        <label v-if="label" :for="forId" class="mb-1.5 block text-label font-semibold text-slate-900">
            {{ label }}
            <span v-if="required" class="text-primary-accessible">*</span>
        </label>

        <slot />

        <p v-if="error" class="mt-1 text-body text-red-600">{{ error }}</p>
        <p v-else-if="hint" class="mt-1.5 flex items-center gap-1.5 text-label text-slate-500">
            <InformationCircleIcon class="h-3.5 w-3.5 flex-shrink-0 text-slate-400" />
            <span>{{ hint }}</span>
        </p>
    </div>
</template>
