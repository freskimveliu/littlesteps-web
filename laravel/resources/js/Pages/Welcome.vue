<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    laravelVersion: string;
    phpVersion: string;
}>();

const stack = [
    { name: 'Laravel', detail: 'PHP 8.4 · FPM' },
    { name: 'Inertia', detail: 'v3 · no API layer' },
    { name: 'Vue', detail: 'v3.5 · TypeScript' },
    { name: 'Tailwind', detail: 'v4 · Vite plugin' },
];
</script>

<template>
    <Head title="Welcome" />

    <div class="flex min-h-dvh items-center justify-center bg-zinc-50 p-6 dark:bg-zinc-950">
        <main class="w-full max-w-2xl">
            <div
                class="rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
                    Little Steps
                </p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">
                    The stack is up and running.
                </h1>
                <p class="mt-3 text-zinc-600 dark:text-zinc-400">
                    This page is a Vue component rendered through Inertia and styled with
                    Tailwind, served by Nginx and PHP-FPM inside Docker.
                </p>

                <dl class="mt-8 grid grid-cols-2 gap-4">
                    <div
                        v-for="item in stack"
                        :key="item.name"
                        class="rounded-xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800/50"
                    >
                        <dt class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ item.name }}
                        </dt>
                        <dd class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ item.detail }}
                        </dd>
                    </div>
                </dl>

                <p class="mt-8 border-t border-zinc-200 pt-4 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    Laravel {{ laravelVersion }} on PHP {{ phpVersion }}
                </p>
            </div>
        </main>
    </div>
</template>
