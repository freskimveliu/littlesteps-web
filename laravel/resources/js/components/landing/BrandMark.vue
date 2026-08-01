<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    size?: number;
}

const props = withDefaults(defineProps<Props>(), { size: 40 });

/* The tile is the app icon itself; iOS masks it at 24%, so match that here. */
const style = computed(() => ({
    width: `${props.size}px`,
    height: `${props.size}px`,
    borderRadius: `${props.size * 0.24}px`,
}));
</script>

<template>
    <img
        src="/img/app-icon.png"
        alt=""
        width="256"
        height="256"
        class="inline-block shrink-0 object-cover select-none"
        :style="style"
    />
</template>
