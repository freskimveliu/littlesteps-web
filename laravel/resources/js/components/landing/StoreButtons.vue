<script setup lang="ts">
import Ion from './Ion.vue';

interface Props {
    tone?: 'dark' | 'light';
}

withDefaults(defineProps<Props>(), { tone: 'dark' });

const STORES = [
    { icon: 'logo-apple', small: 'Coming soon to the', big: 'App Store' },
    { icon: 'logo-google-playstore', small: 'Coming soon to', big: 'Google Play' },
] as const;
</script>

<template>
    <div class="flex flex-wrap items-center gap-3">
        <span
            v-for="store in STORES"
            :key="store.big"
            class="sheen flex cursor-default items-center gap-3 rounded-2xl px-5 py-3 transition-transform duration-300 hover:-translate-y-0.5"
            :class="
                tone === 'dark'
                    ? 'bg-ink text-white shadow-lg shadow-ink/25'
                    : 'bg-white/20 text-white ring-1 ring-white/35 backdrop-blur-sm'
            "
        >
            <Ion :name="store.icon" :size="26" />
            <span class="text-left leading-tight">
                <span class="block text-[10px] tracking-wide opacity-70">{{ store.small }}</span>
                <span class="block font-display text-lg font-extrabold">{{ store.big }}</span>
            </span>
        </span>
    </div>
</template>
