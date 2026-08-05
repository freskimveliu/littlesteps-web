<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

import BrandMark from '@/components/landing/BrandMark.vue';
import Ion from '@/components/landing/Ion.vue';
import type { IconName } from '@/components/landing/icons';
import { useReveal } from '@/composables/useReveal';
import { LEGAL } from '@/support/legal';

interface Props {
    current: 'terms' | 'privacy';
    title: string;
    eyebrow: string;
    icon: IconName;
    updated: string;
    intro: string;
    description: string;
}

const props = defineProps<Props>();

const SIBLING = {
    terms: { href: '/privacy', label: 'Privacy Policy' },
    privacy: { href: '/terms', label: 'Terms & Conditions' },
} as const;

const body = ref<HTMLElement | null>(null);
const toc = ref<{ id: string; heading: string }[]>([]);
const active = ref('');

let observer: IntersectionObserver | null = null;

useReveal();

onMounted(() => {
    const sections = Array.from(body.value?.querySelectorAll<HTMLElement>('section[data-toc]') ?? []);

    toc.value = sections.map((section) => ({ id: section.id, heading: section.dataset.toc ?? '' }));
    active.value = toc.value[0]?.id ?? '';

    if (!('IntersectionObserver' in window)) return;

    observer = new IntersectionObserver(
        (entries) => {
            const visible = entries.filter((entry) => entry.isIntersecting);
            if (visible.length === 0) return;

            active.value = visible.sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0].target.id;
        },
        { rootMargin: '-88px 0px -70% 0px', threshold: 0 },
    );

    sections.forEach((section) => observer?.observe(section));
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <Head>
        <title>{{ props.title }}</title>
        <meta name="description" :content="props.description" />
        <meta name="theme-color" content="#7E5EBF" />
    </Head>

    <div class="landing-root min-h-dvh bg-canvas font-sans text-ink">
        <header class="fixed inset-x-0 top-0 z-50 bg-canvas/85 shadow-[0_1px_0_rgba(126,94,191,0.12)] backdrop-blur-xl">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-4 lg:px-10">
                <Link href="/" class="flex items-center gap-2.5">
                    <BrandMark :size="34" />
                    <span class="font-display text-lg font-extrabold tracking-tight text-ink">
                        Little<span class="text-primary">Steps</span>
                    </span>
                </Link>

                <div class="flex items-center gap-2">
                    <Link
                        :href="SIBLING[props.current].href"
                        class="hidden rounded-full px-4 py-2 text-sm font-semibold text-ink/60 transition-colors hover:bg-primary/8 hover:text-primary sm:inline-flex"
                    >
                        {{ SIBLING[props.current].label }}
                    </Link>
                    <Link
                        href="/"
                        class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-4 py-2 text-sm font-bold text-primary transition-colors hover:bg-primary hover:text-white"
                    >
                        <Ion name="arrow-back" :size="14" />
                        Back to site
                    </Link>
                </div>
            </div>
        </header>

        <section class="relative overflow-hidden px-5 pt-28 pb-12 lg:px-10 lg:pt-36">
            <div class="pointer-events-none absolute inset-0 z-0">
                <div class="drift absolute -top-40 -left-32 h-[26rem] w-[26rem] rounded-full bg-primary/20 blur-3xl" />
                <div class="drift absolute -top-24 right-0 h-[22rem] w-[22rem] rounded-full bg-butter/50 blur-3xl" style="--float-delay: -6s" />
            </div>

            <div class="relative z-10 mx-auto max-w-5xl">
                <span
                    class="reveal inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-xs font-bold tracking-wide text-primary shadow-sm shadow-primary/10 ring-1 ring-primary/10"
                >
                    <Ion :name="props.icon" :size="14" />
                    {{ props.eyebrow }}
                </span>

                <h1
                    class="reveal mt-6 font-display text-[2.4rem] leading-[1] font-extrabold tracking-tight text-ink sm:text-5xl"
                    style="--reveal-delay: 80ms"
                >
                    {{ props.title }}
                </h1>

                <p class="reveal mt-6 max-w-2xl text-lg leading-relaxed text-ink/60" style="--reveal-delay: 160ms">
                    {{ props.intro }}
                </p>

                <div class="reveal mt-7 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-ink/50" style="--reveal-delay: 240ms">
                    <span class="flex items-center gap-2">
                        <Ion name="time" :size="15" class="text-primary" />
                        Last updated {{ props.updated }}
                    </span>
                    <span class="flex items-center gap-2">
                        <Ion name="mail" :size="15" class="text-primary" />
                        <a :href="`mailto:${LEGAL.contactEmail}`" class="font-semibold text-primary hover:underline">
                            {{ LEGAL.contactEmail }}
                        </a>
                    </span>
                </div>
            </div>
        </section>

        <section class="px-5 pb-24 lg:px-10">
            <div class="mx-auto grid max-w-5xl gap-10 lg:grid-cols-[15rem_1fr]">
                <aside class="hidden lg:block">
                    <nav class="sticky top-28 space-y-1" aria-label="On this page">
                        <p class="px-3 pb-2 text-xs font-extrabold tracking-[0.2em] text-primary/70 uppercase">On this page</p>
                        <a
                            v-for="item in toc"
                            :key="item.id"
                            :href="`#${item.id}`"
                            class="block rounded-xl px-3 py-2 text-sm leading-snug font-semibold transition-colors"
                            :class="active === item.id ? 'bg-primary/10 text-primary' : 'text-ink/50 hover:bg-primary/5 hover:text-primary'"
                        >
                            {{ item.heading }}
                        </a>
                    </nav>
                </aside>

                <div>
                    <div ref="body" class="space-y-10 rounded-[2rem] bg-white p-7 shadow-sm shadow-primary/5 sm:p-10">
                        <slot />
                    </div>

                    <div class="reveal mt-6 flex flex-col items-start gap-4 rounded-[2rem] bg-linear-135 from-primary via-violet-soft to-violet-deep p-7 text-white shadow-xl shadow-primary/25 sm:flex-row sm:items-center sm:p-8">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/20">
                            <Ion name="chatbubble-ellipses-outline" :size="22" />
                        </span>
                        <div class="flex-1">
                            <p class="font-display text-xl font-extrabold">Something here unclear?</p>
                            <p class="mt-1.5 text-sm leading-relaxed text-white/70">
                                Write to us and a person will answer. No ticket number, no phone tree.
                            </p>
                        </div>
                        <a
                            :href="`mailto:${LEGAL.contactEmail}`"
                            class="rounded-full bg-white px-5 py-2.5 text-sm font-bold text-primary transition-transform duration-200 hover:-translate-y-0.5"
                        >
                            {{ LEGAL.contactEmail }}
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <footer class="border-t border-primary/10 px-5 py-12 lg:px-10">
            <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-6 sm:flex-row">
                <div class="flex items-center gap-3">
                    <BrandMark :size="36" />
                    <div>
                        <p class="font-display text-lg leading-none font-extrabold text-ink">
                            Little<span class="text-primary">Steps</span>
                        </p>
                        <p class="mt-1 text-xs text-ink/40">Every little step, kept forever.</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm">
                    <Link href="/" class="font-semibold text-ink/50 transition-colors hover:text-primary">Home</Link>
                    <Link href="/terms" class="font-semibold text-ink/50 transition-colors hover:text-primary">Terms &amp; Conditions</Link>
                    <Link href="/privacy" class="font-semibold text-ink/50 transition-colors hover:text-primary">Privacy Policy</Link>
                </div>
            </div>

            <p class="mx-auto mt-8 max-w-5xl text-center text-sm text-ink/40 sm:text-left">
                © {{ new Date().getFullYear() }} {{ LEGAL.entity }}. Made for the ones who keep growing.
            </p>
        </footer>
    </div>
</template>
