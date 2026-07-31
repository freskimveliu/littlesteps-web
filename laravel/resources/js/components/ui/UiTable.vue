<script setup lang="ts">
import type { Component } from 'vue';
import UiSpinner from './UiSpinner.vue';

interface Props {
    loading?: boolean;
    empty?: boolean;
    emptyTitle?: string;
    emptyDescription?: string;
    emptyIcon?: Component;
    bare?: boolean;
    tableClass?: string;
}

withDefaults(defineProps<Props>(), {
    loading: false,
    empty: false,
    emptyTitle: 'Nothing here yet',
    emptyDescription: '',
    bare: false,
    tableClass: '',
});
</script>

<template>
    <div
        :class="
            bare
                ? 'overflow-visible rounded-none bg-transparent shadow-none'
                : 'overflow-hidden rounded-[14px] bg-white shadow-[0_1px_3px_rgba(15,23,42,0.06)]'
        "
    >
        <div
            v-if="$slots.toolbar"
            class="flex flex-wrap items-center justify-end gap-3"
            :class="bare ? 'pb-3' : 'border-b border-[#f0f4f8] px-6 py-5'"
        >
            <slot name="toolbar" />
        </div>

        <div v-if="loading" class="flex justify-center px-6 py-12">
            <UiSpinner size="lg" tone="primary" />
        </div>

        <div v-else-if="empty" class="flex flex-col items-center px-6 pt-12 pb-8 text-center">
            <component :is="emptyIcon" v-if="emptyIcon" class="mb-4 h-12 w-12 text-slate-300" />
            <p class="mb-1.5 text-sm leading-[1.3] font-bold text-slate-900">{{ emptyTitle }}</p>
            <p v-if="emptyDescription" class="max-w-xs text-body text-slate-500">{{ emptyDescription }}</p>
        </div>

        <div v-else class="overflow-x-auto">
            <table
                :class="[
                    'min-w-full [&_tbody_tr]:border-b [&_tbody_tr]:border-slate-50 [&_tbody_tr:last-child]:border-b-0',
                    tableClass,
                ]"
            >
                <thead>
                    <tr class="border-b border-[#f0f4f8]">
                        <slot name="header" />
                    </tr>
                </thead>
                <tbody>
                    <slot name="body" />
                </tbody>
            </table>
        </div>

        <slot name="footer" />
    </div>
</template>
