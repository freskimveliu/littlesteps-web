<script setup lang="ts">
defineProps<{ direction?: 'asc' | 'desc' | null }>();
</script>

<template>
    <svg viewBox="0 0 8 11" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
            d="M4 0.5L7 4H1L4 0.5Z"
            :class="direction === 'asc' ? 'fill-current' : 'fill-current opacity-30'"
        />
        <path
            d="M4 10.5L1 7H7L4 10.5Z"
            :class="direction === 'desc' ? 'fill-current' : 'fill-current opacity-30'"
        />
    </svg>
</template>
