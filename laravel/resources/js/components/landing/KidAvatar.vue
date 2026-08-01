<script setup lang="ts">
interface Props {
    size?: number;
    variant?: 'a' | 'b' | 'c';
}

withDefaults(defineProps<Props>(), { size: 56, variant: 'a' });

const PALETTES = {
    a: { bg: '#FFE5A0', skin: '#F7C9A8', hair: '#6B4A2F' },
    b: { bg: '#C5B3E6', skin: '#EFB894', hair: '#2F2A3A' },
    c: { bg: '#B5EAD7', skin: '#F9D8BE', hair: '#C97A3A' },
} as const;
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 64 64"
        fill="none"
        class="shrink-0"
        role="img"
        aria-label="Child avatar illustration"
    >
        <circle cx="32" cy="32" r="32" :fill="PALETTES[variant].bg" />
        <circle cx="32" cy="30" r="17" :fill="PALETTES[variant].skin" />
        <path
            d="M15 29a17 17 0 0 1 34 0c0-4-3-6-6-7-3.5 3-9 4-14 2.5-3 1-6.5 2-8 4.5-3 1-6 0-6 0Z"
            :fill="PALETTES[variant].hair"
        />
        <path d="M31 8.5c.5-3 3-4.5 5-3.5-1.5.5-2 2-1.5 3.5" :fill="PALETTES[variant].hair" />
        <ellipse cx="25.5" cy="31" rx="2.1" ry="2.6" fill="#3B2F45" />
        <ellipse cx="38.5" cy="31" rx="2.1" ry="2.6" fill="#3B2F45" />
        <circle cx="26.4" cy="30.2" r="0.7" fill="#fff" />
        <circle cx="39.4" cy="30.2" r="0.7" fill="#fff" />
        <circle cx="21" cy="36" r="3" fill="#F4A6A6" opacity="0.55" />
        <circle cx="43" cy="36" r="3" fill="#F4A6A6" opacity="0.55" />
        <path d="M28 38.5c1.4 1.8 6.6 1.8 8 0" stroke="#8A5A46" stroke-width="1.8" stroke-linecap="round" />
        <path d="M32 47c9 0 16 4.2 18.5 10.4A32 32 0 0 1 13.5 57.4C16 51.2 23 47 32 47Z" fill="#7E5EBF" />
        <path d="M32 47c2.6 0 5.1.4 7.3 1-1.6 3-4.2 4.8-7.3 4.8s-5.7-1.8-7.3-4.8c2.2-.6 4.7-1 7.3-1Z" fill="#fff" opacity="0.85" />
    </svg>
</template>
