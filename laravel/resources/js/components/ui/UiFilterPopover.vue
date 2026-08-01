<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import FunnelIcon from '@heroicons/vue/24/outline/esm/FunnelIcon.js';
import UiButton from './UiButton.vue';

withDefaults(defineProps<{ activeCount?: number; title?: string }>(), { activeCount: 0 });

const emit = defineEmits<{ apply: []; reset: [] }>();

const open = ref(false);
const trigger = ref<HTMLElement | null>(null);
const panel = ref<HTMLElement | null>(null);
const position = ref({ top: 0, right: 0 });

/**
 * The panel is teleported out of the table card, which clips it with
 * overflow-hidden — so it is placed against the trigger's viewport rect
 * instead of being positioned by an ancestor.
 */
function place() {
    if (!trigger.value) return;
    const rect = trigger.value.getBoundingClientRect();
    position.value = { top: rect.bottom + 8, right: window.innerWidth - rect.right };
}

function toggle() {
    open.value = !open.value;
    if (open.value) place();
}

function reposition() {
    if (open.value) place();
}

function onClickOutside(event: MouseEvent) {
    const target = event.target as Node;
    if (trigger.value?.contains(target) || panel.value?.contains(target)) return;
    open.value = false;
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') open.value = false;
}

onMounted(() => {
    document.addEventListener('mousedown', onClickOutside);
    document.addEventListener('keydown', onKeydown);
    window.addEventListener('resize', reposition);
    window.addEventListener('scroll', reposition, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onClickOutside);
    document.removeEventListener('keydown', onKeydown);
    window.removeEventListener('resize', reposition);
    window.removeEventListener('scroll', reposition, true);
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
    <button
        ref="trigger"
        type="button"
        class="relative flex h-[34px] cursor-pointer items-center gap-2 rounded-ui border px-[15px] text-[13px] font-medium whitespace-nowrap transition-colors"
        :class="
            open || activeCount > 0
                ? 'border-primary bg-primary/5 text-primary-accessible'
                : 'border-slate-200 bg-white text-[#555555] hover:border-primary hover:text-primary-accessible'
        "
        @click="toggle"
    >
        <FunnelIcon class="h-3.5 w-3.5" />
        Filters
        <span
            v-if="activeCount > 0"
            class="absolute -top-1.5 -right-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-primary-text"
        >
            {{ activeCount }}
        </span>
    </button>

    <!-- No <Transition> inside the <Teleport> — see the note in UiModal. -->
    <Teleport to="body">
        <div
            v-if="open"
            ref="panel"
            class="ui-modal fixed z-50 w-80 rounded-[14px] bg-white shadow-[0_8px_30px_rgba(15,23,42,0.12)]"
            :style="{ top: `${position.top}px`, right: `${position.right}px` }"
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
    </Teleport>
</template>
