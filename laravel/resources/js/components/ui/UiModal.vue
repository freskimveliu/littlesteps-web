<script setup lang="ts">
import { computed, onBeforeUnmount, watch } from 'vue';
import XMarkIcon from '@heroicons/vue/24/outline/esm/XMarkIcon.js';

interface Props {
    title?: string;
    size?: 'sm' | 'md' | 'lg' | 'xl';
    closeOnBackdrop?: boolean;
}

const props = withDefaults(defineProps<Props>(), { size: 'md', closeOnBackdrop: true });

const open = defineModel<boolean>({ default: false });

const widthClass = computed(
    () => ({ sm: 'max-w-sm', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl' })[props.size],
);

function close() {
    open.value = false;
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') close();
}

watch(
    open,
    (isOpen) => {
        if (typeof document === 'undefined') return;
        document.body.style.overflow = isOpen ? 'hidden' : '';
        isOpen
            ? document.addEventListener('keydown', onKeydown)
            : document.removeEventListener('keydown', onKeydown);
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (typeof document === 'undefined') return;
    document.body.style.overflow = '';
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <!--
        No <Transition> here. Inside a <Teleport> its enter-from class could be
        left applied, which pinned the overlay at opacity 0 — an invisible
        full-screen layer that still swallowed every click. The fade is a plain
        CSS animation instead, so the element is either mounted or it is not.
    -->
    <Teleport to="body">
        <div v-if="open" class="ui-modal fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40" @click="closeOnBackdrop && close()" />

                <div
                    class="relative flex max-h-[88vh] w-full flex-col overflow-hidden rounded-[14px] bg-white shadow-xl"
                    :class="widthClass"
                >
                    <div
                        v-if="title || $slots.header"
                        class="flex items-center justify-between border-b border-[#f0f4f8] px-6 pt-5 pb-4"
                    >
                        <slot name="header">
                            <h3 class="text-card-title font-bold text-slate-900">{{ title }}</h3>
                        </slot>
                        <button
                            type="button"
                            class="rounded-ui p-1 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                            @click="close"
                        >
                            <XMarkIcon class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-5">
                        <slot />
                    </div>

                    <div v-if="$slots.footer" class="border-t border-[#f0f4f8] px-6 py-4">
                        <slot name="footer" />
                    </div>
                </div>
        </div>
    </Teleport>
</template>
