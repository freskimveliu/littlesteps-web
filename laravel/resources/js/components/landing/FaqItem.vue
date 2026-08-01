<script setup lang="ts">
import { ref } from 'vue';
import Ion from './Ion.vue';

interface Props {
    question: string;
}

defineProps<Props>();

const open = ref(false);
</script>

<template>
    <div
        class="overflow-hidden rounded-3xl bg-white transition-shadow duration-300"
        :class="open ? 'shadow-xl shadow-primary/10' : 'shadow-sm shadow-primary/5'"
    >
        <button
            type="button"
            class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left"
            :aria-expanded="open"
            @click="open = !open"
        >
            <span class="font-display text-lg font-bold text-ink">{{ question }}</span>
            <span
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition-all duration-300"
                :class="open ? 'rotate-180 bg-primary text-white' : 'bg-primary/10 text-primary'"
            >
                <Ion name="chevron-down" :size="16" />
            </span>
        </button>

        <div class="grid transition-[grid-template-rows] duration-300 ease-out" :class="open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
            <div class="overflow-hidden">
                <p class="px-6 pb-6 text-[15px] leading-relaxed text-ink/60">
                    <slot />
                </p>
            </div>
        </div>
    </div>
</template>
