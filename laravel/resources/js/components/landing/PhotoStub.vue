<script setup lang="ts">
interface Props {
    variant?: 'meadow' | 'evening' | 'nursery';
}

withDefaults(defineProps<Props>(), { variant: 'meadow' });

const SKIES = {
    meadow: ['#FFE9B8', '#FFD8E2'],
    evening: ['#C7B4E8', '#8F73C9'],
    nursery: ['#BFE9DA', '#FFF1C9'],
} as const;

const HILLS = {
    meadow: ['#F7B7C6', '#E88FA6'],
    evening: ['#6E51A8', '#4E3A7D'],
    nursery: ['#8FD5BC', '#5EAD8C'],
} as const;
</script>

<template>
    <div class="relative overflow-hidden">
        <svg viewBox="0 0 200 120" preserveAspectRatio="xMidYMid slice" class="h-full w-full">
            <defs>
                <linearGradient :id="`sky-${variant}`" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" :stop-color="SKIES[variant][0]" />
                    <stop offset="100%" :stop-color="SKIES[variant][1]" />
                </linearGradient>
            </defs>
            <rect width="200" height="120" :fill="`url(#sky-${variant})`" />
            <circle cx="152" cy="30" r="16" fill="#fff" opacity="0.55" />
            <circle cx="40" cy="22" r="3" fill="#fff" opacity="0.7" />
            <circle cx="60" cy="34" r="2" fill="#fff" opacity="0.5" />
            <circle cx="118" cy="18" r="2.5" fill="#fff" opacity="0.6" />
            <path d="M0 96c26-22 52-22 78 0s52 22 78 0 44-14 44-14v38H0Z" :fill="HILLS[variant][0]" />
            <path d="M0 108c30-16 58-16 88 0s54 12 112-6v18H0Z" :fill="HILLS[variant][1]" />
        </svg>
    </div>
</template>
