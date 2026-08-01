<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import BrandMark from './BrandMark.vue';
import Ion from './Ion.vue';

const LINKS = [
    { label: 'The map', href: '#map' },
    { label: 'Memories', href: '#memories' },
    { label: 'Rewards', href: '#rewards' },
    { label: 'Chapters', href: '#chapters' },
    { label: 'Questions', href: '#faq' },
];

const scrolled = ref(false);
const open = ref(false);

function onScroll() {
    scrolled.value = window.scrollY > 12;
}

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
        :class="scrolled ? 'bg-canvas/85 shadow-[0_1px_0_rgba(126,94,191,0.12)] backdrop-blur-xl' : ''"
    >
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 lg:px-10">
            <a href="#top" class="flex items-center gap-2.5">
                <BrandMark :size="38" />
                <span class="font-display text-xl font-extrabold tracking-tight text-ink">
                    Little<span class="text-primary">Steps</span>
                </span>
            </a>

            <nav class="hidden items-center gap-1 lg:flex">
                <a
                    v-for="link in LINKS"
                    :key="link.href"
                    :href="link.href"
                    class="rounded-full px-4 py-2 text-sm font-semibold text-ink/60 transition-colors hover:bg-primary/8 hover:text-primary"
                >
                    {{ link.label }}
                </a>
            </nav>

            <div class="flex items-center gap-2">
                <a
                    href="#get"
                    class="hidden rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-transform duration-200 hover:-translate-y-0.5 sm:inline-flex"
                >
                    Get the app
                </a>
                <button
                    type="button"
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary lg:hidden"
                    :aria-expanded="open"
                    aria-label="Toggle menu"
                    @click="open = !open"
                >
                    <Ion :name="open ? 'close' : 'ellipsis-horizontal'" :size="20" />
                </button>
            </div>
        </div>

        <div v-if="open" class="mx-5 mb-3 rounded-3xl bg-white p-3 shadow-xl shadow-primary/10 lg:hidden">
            <a
                v-for="link in LINKS"
                :key="link.href"
                :href="link.href"
                class="block rounded-2xl px-4 py-3 text-sm font-semibold text-ink/70 hover:bg-primary/8"
                @click="open = false"
            >
                {{ link.label }}
            </a>
            <a
                href="#get"
                class="mt-1 block rounded-2xl bg-primary px-4 py-3 text-center text-sm font-bold text-white"
                @click="open = false"
            >
                Get the app
            </a>
        </div>
    </header>
</template>
