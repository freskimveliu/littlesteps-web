<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    title?: string;
    flush?: boolean;
    bodyClass?: string;
}

const props = withDefaults(defineProps<Props>(), { flush: false, bodyClass: '' });

const cardClasses = computed(() =>
    ['overflow-hidden rounded-[14px] bg-white shadow-[0_1px_3px_rgba(15,23,42,0.06)]'].join(' '),
);

const resolvedBodyClass = computed(() => props.bodyClass || (props.flush ? '' : 'px-6 py-5'));
</script>

<template>
    <div :class="cardClasses">
        <div v-if="title || $slots.header" class="border-b border-[#f0f4f8] px-6 pt-5 pb-4">
            <slot name="header">
                <h3 class="text-card-title font-bold text-slate-900">{{ title }}</h3>
            </slot>
        </div>

        <div :class="resolvedBodyClass">
            <slot />
        </div>

        <div v-if="$slots.footer" class="border-t border-[#f0f4f8] bg-white px-6 py-4">
            <slot name="footer" />
        </div>
    </div>
</template>
