<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import CheckCircleIcon from '@heroicons/vue/24/outline/esm/CheckCircleIcon.js';
import ExclamationCircleIcon from '@heroicons/vue/24/outline/esm/ExclamationCircleIcon.js';

const page = usePage();

const visible = ref(false);
const timer = ref<ReturnType<typeof setTimeout> | null>(null);

const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
const message = computed(() => flash.value?.success ?? flash.value?.error ?? '');
const isError = computed(() => !!flash.value?.error);

watch(
    message,
    (value) => {
        if (!value) return;
        visible.value = true;
        if (timer.value) clearTimeout(timer.value);
        timer.value = setTimeout(() => (visible.value = false), 4000);
    },
    { immediate: true },
);
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        leave-active-class="transition duration-150 ease-in"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div v-if="visible && message" class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2">
            <div
                class="flex items-center gap-2 rounded-ui px-4 py-2.5 text-body font-medium text-white shadow-lg"
                :class="isError ? 'bg-red-600' : 'bg-slate-900'"
            >
                <component :is="isError ? ExclamationCircleIcon : CheckCircleIcon" class="h-4 w-4 flex-shrink-0" />
                {{ message }}
            </div>
        </div>
    </Transition>
</template>
