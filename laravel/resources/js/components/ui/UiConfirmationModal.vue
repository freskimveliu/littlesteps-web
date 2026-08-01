<script setup lang="ts">
import ExclamationTriangleIcon from '@heroicons/vue/24/outline/esm/ExclamationTriangleIcon.js';
import UiModal from './UiModal.vue';
import UiButton from './UiButton.vue';

withDefaults(
    defineProps<{
        title: string;
        message?: string;
        confirmText?: string;
        cancelText?: string;
        confirmVariant?: 'primary' | 'danger';
        loading?: boolean;
    }>(),
    { confirmText: 'Confirm', cancelText: 'Cancel', confirmVariant: 'primary', loading: false },
);

const emit = defineEmits<{ confirm: [] }>();

const open = defineModel<boolean>({ default: false });
</script>

<template>
    <UiModal v-model="open" size="sm">
        <div class="flex gap-4">
            <div
                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full"
                :class="confirmVariant === 'danger' ? 'bg-red-100 text-red-600' : 'bg-primary/10 text-primary'"
            >
                <ExclamationTriangleIcon class="h-5 w-5" />
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900">{{ title }}</h3>
                <p v-if="message" class="mt-1 text-body text-slate-500">{{ message }}</p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <UiButton variant="outline" @click="open = false">{{ cancelText }}</UiButton>
                <UiButton :variant="confirmVariant" :loading="loading" @click="emit('confirm')">
                    {{ confirmText }}
                </UiButton>
            </div>
        </template>
    </UiModal>
</template>
