<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    align?: 'left' | 'center' | 'right';
    valign?: 'top' | 'middle' | 'bottom';
    cellClass?: string;
}

const props = withDefaults(defineProps<Props>(), { align: 'left', valign: 'middle', cellClass: '' });

const alignmentClass = computed(
    () => ({ left: 'text-left', center: 'text-center', right: 'text-right' })[props.align],
);

const valignClass = computed(
    () => ({ top: 'align-top', middle: 'align-middle', bottom: 'align-bottom' })[props.valign],
);
</script>

<template>
    <td :class="['px-6 py-3 text-body text-slate-700', alignmentClass, valignClass, cellClass]">
        <slot />
    </td>
</template>
