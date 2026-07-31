<script setup lang="ts">
import { computed } from 'vue';
import UiTableHeader from './UiTableHeader.vue';
import UiSortIcon from './UiSortIcon.vue';

export type SortOrder = 'asc' | 'desc';

interface Props {
    sortKey: string;
    activeKey?: string | null;
    activeOrder?: SortOrder | null;
    align?: 'left' | 'center' | 'right';
    cellClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
    activeKey: null,
    activeOrder: null,
    align: 'left',
    cellClass: '',
});

const emit = defineEmits<{ sort: [key: string, order: SortOrder] }>();

const isActive = computed(() => props.activeKey === props.sortKey);

function toggle() {
    if (!isActive.value) return emit('sort', props.sortKey, 'asc');
    emit('sort', props.sortKey, props.activeOrder === 'asc' ? 'desc' : 'asc');
}
</script>

<template>
    <UiTableHeader :align="align" :cell-class="cellClass">
        <button
            type="button"
            class="inline-flex items-center gap-1 hover:text-gray-600"
            :class="isActive ? 'text-slate-900' : ''"
            @click="toggle"
        >
            <span><slot /></span>
            <UiSortIcon class="h-[11px] w-2" :direction="isActive ? activeOrder : null" />
        </button>
    </UiTableHeader>
</template>
