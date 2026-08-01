<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

interface Props {
    to: number;
    duration?: number;
}

const props = withDefaults(defineProps<Props>(), { duration: 1400 });

const el = ref<HTMLElement | null>(null);
const shown = ref(0);
let frame = 0;
let observer: IntersectionObserver | null = null;

function run() {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduced) {
        shown.value = props.to;
        return;
    }

    const start = performance.now();
    const tick = (now: number) => {
        const t = Math.min(1, (now - start) / props.duration);
        shown.value = Math.round(props.to * (1 - Math.pow(1 - t, 3)));
        if (t < 1) frame = requestAnimationFrame(tick);
    };
    frame = requestAnimationFrame(tick);
}

onMounted(() => {
    if (!el.value || !('IntersectionObserver' in window)) {
        shown.value = props.to;
        return;
    }

    observer = new IntersectionObserver(
        (entries) => {
            if (!entries[0].isIntersecting) return;
            observer?.disconnect();
            run();
        },
        { threshold: 0.4 },
    );
    observer.observe(el.value);
});

onBeforeUnmount(() => {
    cancelAnimationFrame(frame);
    observer?.disconnect();
});
</script>

<template>
    <span ref="el">{{ shown }}</span>
</template>
