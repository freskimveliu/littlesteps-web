<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    size?: number;
}

const props = withDefaults(defineProps<Props>(), { size: 40 });

/* The icon artwork is 250px type on a 1024px tile — keep that ratio at any size. */
const style = computed(() => ({
    width: `${props.size}px`,
    height: `${props.size}px`,
    fontSize: `${props.size * 0.244}px`,
    borderRadius: `${props.size * 0.24}px`,
}));
</script>

<template>
    <span
        class="relative inline-flex shrink-0 flex-col items-center justify-center overflow-hidden bg-linear-135 from-violet-soft via-primary to-violet-deep font-display leading-[0.92] font-extrabold tracking-tight select-none"
        :style="style"
        aria-hidden="true"
    >
        <span
            class="absolute inset-0"
            style="
                background: radial-gradient(
                    60% 55% at 28% 16%,
                    rgba(255, 255, 255, 0.22),
                    rgba(255, 255, 255, 0) 70%
                );
            "
        />
        <span class="relative text-white">Little</span>
        <span class="relative text-butter">Steps</span>
    </span>
</template>
