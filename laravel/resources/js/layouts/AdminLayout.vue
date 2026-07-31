<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Squares2X2Icon from '@heroicons/vue/24/outline/esm/Squares2X2Icon.js';
import BookOpenIcon from '@heroicons/vue/24/outline/esm/BookOpenIcon.js';
import FlagIcon from '@heroicons/vue/24/outline/esm/FlagIcon.js';
import TagIcon from '@heroicons/vue/24/outline/esm/TagIcon.js';
import TrophyIcon from '@heroicons/vue/24/outline/esm/TrophyIcon.js';
import ChartBarIcon from '@heroicons/vue/24/outline/esm/ChartBarIcon.js';
import SparklesIcon from '@heroicons/vue/24/outline/esm/SparklesIcon.js';
import Cog6ToothIcon from '@heroicons/vue/24/outline/esm/Cog6ToothIcon.js';
import ArrowRightStartOnRectangleIcon from '@heroicons/vue/24/outline/esm/ArrowRightStartOnRectangleIcon.js';
import UiToast from '../components/ui/UiToast.vue';

const page = usePage();

const user = computed(() => page.props.auth?.user as { name: string; email: string | null } | undefined);

const nav = [
    { label: 'Dashboard', href: '/admin', icon: Squares2X2Icon, exact: true },
    { label: 'Chapters', href: '/admin/chapters', icon: BookOpenIcon },
    { label: 'Steps', href: '/admin/steps', icon: FlagIcon },
    { label: 'Categories', href: '/admin/categories', icon: TagIcon },
    { label: 'Badges', href: '/admin/badges', icon: TrophyIcon },
    { label: 'Levels', href: '/admin/levels', icon: ChartBarIcon },
    { label: 'Prompts', href: '/admin/prompts', icon: SparklesIcon },
    { label: 'Settings', href: '/admin/settings', icon: Cog6ToothIcon },
];

function isActive(item: (typeof nav)[number]): boolean {
    const current = page.url.split('?')[0];
    return item.exact ? current === item.href : current.startsWith(item.href);
}

function logout() {
    router.post('/admin/logout');
}
</script>

<template>
    <div class="flex min-h-screen">
        <aside class="flex w-[220px] flex-shrink-0 flex-col border-r border-[#e6ecf5] bg-white">
            <div class="px-5 py-6">
                <p class="text-sm font-bold text-slate-900">LittleSteps</p>
                <p class="text-label text-slate-400">Admin</p>
            </div>

            <nav class="flex-1 px-3">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    class="mb-0.5 flex items-center gap-3 rounded-ui px-3 py-2 text-body font-medium transition-colors"
                    :class="
                        isActive(item)
                            ? 'bg-primary/10 text-primary-accessible'
                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                    "
                >
                    <component :is="item.icon" class="h-4 w-4 flex-shrink-0" />
                    {{ item.label }}
                </Link>
            </nav>

            <div class="border-t border-[#f0f4f8] px-5 py-4">
                <p class="truncate text-body font-medium text-slate-700">{{ user?.name }}</p>
                <p class="truncate text-label text-slate-400">{{ user?.email }}</p>
                <button
                    type="button"
                    class="mt-2 inline-flex items-center gap-1.5 text-label text-slate-500 transition-colors hover:text-red-600"
                    @click="logout"
                >
                    <ArrowRightStartOnRectangleIcon class="h-3.5 w-3.5" />
                    Sign out
                </button>
            </div>
        </aside>

        <main class="min-w-0 flex-1 px-10 py-8">
            <div class="mx-auto max-w-[1400px]">
                <slot />
            </div>
        </main>

        <UiToast />
    </div>
</template>
