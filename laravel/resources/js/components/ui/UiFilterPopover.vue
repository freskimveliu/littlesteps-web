<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import FunnelIcon from '@heroicons/vue/24/outline/esm/FunnelIcon.js';
import UiButton from './UiButton.vue';

withDefaults(defineProps<{ activeCount?: number; title?: string }>(), { activeCount: 0 });

const emit = defineEmits<{ apply: []; reset: [] }>();

const open = ref(false);
const root = ref<HTMLElement | null>(null);

function onClickOutside(event: MouseEvent) {
    if (root.value && !root.value.contains(event.target as Node)) open.value = false;
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') open.value = false;
}

onMounted(() => {
    document.addEventListener('mousedown', onClickOutside);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onClickOutside);
    document.removeEventListener('keydown', onKeydown);
});

function apply() {
    emit('apply');
    open.value = false;
}

function reset() {
    emit('reset');
    open.value = false;
}
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="relative flex h-[34px] w-[34px] items-center justify-center rounded-ui border transition-colors"
            :class="
                open || activeCount > 0
                    ? 'border-primary bg-primary/5 text-primary-accessible'
                    : 'border-slate-200 bg-white text-[#555555] hover:border-primary hover:text-primary-accessible'
            "
            title="Filters"
            @click="open = !open"
        >
            <FunnelIcon class="h-4 w-4" />
            <span
                v-if="activeCount > 0"
                class="absolute -top-1.5 -right-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-primary-text"
            >
                {{ activeCount }}
            </span>
        </button>

        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            leave-active-class="transition duration-75 ease-in"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div
                v-if="open"
                class="absolute top-full right-0 z-30 mt-2 w-80 rounded-[14px] bg-white shadow-[0_8px_30px_rgba(15,23,42,0.12)]"
            >
                <div class="flex flex-col gap-4 px-5 py-5">
                    <p v-if="title" class="text-label font-semibold tracking-wide text-slate-400 uppercase">
                        {{ title }}
                    </p>
                    <slot />
                </div>

                <div class="flex gap-3 border-t border-[#f0f4f8] px-5 py-4">
                    <UiButton variant="outline" full-width @click="reset">Reset</UiButton>
                    <UiButton full-width @click="apply">Apply</UiButton>
                </div>
            </div>
        </Transition>
    </div>
</template>
