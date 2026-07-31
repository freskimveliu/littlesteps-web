<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    align?: 'left' | 'center' | 'right';
    cellClass?: string;
}

const props = withDefaults(defineProps<Props>(), { align: 'left', cellClass: '' });

const alignmentClass = computed(
    () => ({ left: 'text-left', center: 'text-center', right: 'text-right' })[props.align],
);
</script>

<template>
    <th scope="col" :class="['px-6 py-3 align-middle text-label font-medium text-[#aaaaaa]', alignmentClass, cellClass]">
        <slot />
    </th>
</template>
