<script setup lang="ts">
import { ICONS, type IconName } from './icons';

interface Props {
    name: IconName;
    size?: number;
}

withDefaults(defineProps<Props>(), { size: 16 });
</script>

<template>
    <span class="ion" :style="{ fontSize: `${size}px`, width: `${size}px`, height: `${size}px` }" aria-hidden="true">{{
        ICONS[name]
    }}</span>
</template>
