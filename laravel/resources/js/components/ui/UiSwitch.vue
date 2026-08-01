<script setup lang="ts">
defineProps<{ label?: string; disabled?: boolean }>();

const model = defineModel<boolean>({ default: false });
</script>

<template>
    <label class="inline-flex cursor-pointer items-center gap-2" :class="{ 'cursor-not-allowed opacity-60': disabled }">
        <button
            type="button"
            role="switch"
            :aria-checked="model"
            :disabled="disabled"
            class="relative h-5 w-9 flex-shrink-0 rounded-full transition-colors duration-150"
            :class="model ? 'bg-primary' : 'bg-slate-300'"
            @click="model = !model"
        >
            <span
                class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform duration-150"
                :class="model ? 'translate-x-4' : 'translate-x-0'"
            />
        </button>
        <span v-if="label" class="text-body text-slate-700">{{ label }}</span>
    </label>
</template>
