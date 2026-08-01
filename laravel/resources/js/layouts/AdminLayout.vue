<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Squares2X2Icon from '@heroicons/vue/24/outline/esm/Squares2X2Icon.js';
import BookOpenIcon from '@heroicons/vue/24/outline/esm/BookOpenIcon.js';
import FlagIcon from '@heroicons/vue/24/outline/esm/FlagIcon.js';
import TagIcon from '@heroicons/vue/24/outline/esm/TagIcon.js';
import TrophyIcon from '@heroicons/vue/24/outline/esm/TrophyIcon.js';
import ChartBarIcon from '@heroicons/vue/24/outline/esm/ChartBarIcon.js';
import SparklesIcon from '@heroicons/vue/24/outline/esm/SparklesIcon.js';
import Cog6ToothIcon from '@heroicons/vue/24/outline/esm/Cog6ToothIcon.js';
import UsersIcon from '@heroicons/vue/24/outline/esm/UsersIcon.js';
import FaceSmileIcon from '@heroicons/vue/24/outline/esm/FaceSmileIcon.js';
import GiftIcon from '@heroicons/vue/24/outline/esm/GiftIcon.js';
import ArrowRightStartOnRectangleIcon from '@heroicons/vue/24/outline/esm/ArrowRightStartOnRectangleIcon.js';
import ChevronLeftIcon from '@heroicons/vue/24/outline/esm/ChevronLeftIcon.js';
import UiToast from '../components/ui/UiToast.vue';

const page = usePage();

const user = computed(() => page.props.auth?.user as { name: string; email: string | null } | undefined);

interface NavItem {
    label: string;
    href: string;
    icon: unknown;
    exact?: boolean;
}

const groups: { title: string | null; items: NavItem[] }[] = [
    {
        title: null,
        items: [{ label: 'Dashboard', href: '/admin', icon: Squares2X2Icon, exact: true }],
    },
    {
        title: 'Families',
        items: [
            { label: 'Parents', href: '/admin/users', icon: UsersIcon },
            { label: 'Children', href: '/admin/children', icon: FaceSmileIcon },
            { label: 'Gifts', href: '/admin/gifts', icon: GiftIcon },
        ],
    },
    {
        title: 'Catalogue',
        items: [
            { label: 'Milestones', href: '/admin/milestones', icon: BookOpenIcon },
            { label: 'Steps', href: '/admin/steps', icon: FlagIcon },
            { label: 'Categories', href: '/admin/categories', icon: TagIcon },
            { label: 'Badges', href: '/admin/badges', icon: TrophyIcon },
            { label: 'Levels', href: '/admin/levels', icon: ChartBarIcon },
            { label: 'Prompts', href: '/admin/prompts', icon: SparklesIcon },
        ],
    },
    {
        title: null,
        items: [{ label: 'Settings', href: '/admin/settings', icon: Cog6ToothIcon }],
    },
];

function isActive(item: NavItem): boolean {
    const current = page.url.split('?')[0];
    return item.exact ? current === item.href : current.startsWith(item.href);
}

// Kept in localStorage so the rail does not flick back open on every visit.
const collapsed = ref(false);

onMounted(() => {
    collapsed.value = localStorage.getItem('admin.sidebar') === 'collapsed';
});

function toggleCollapsed() {
    collapsed.value = !collapsed.value;
    localStorage.setItem('admin.sidebar', collapsed.value ? 'collapsed' : 'open');
}

const initials = computed(() =>
    (user.value?.name ?? '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join(''),
);

function logout() {
    router.post('/admin/logout');
}
</script>

<template>
    <div class="flex min-h-screen">
        <aside
            class="sticky top-0 flex h-screen flex-shrink-0 flex-col bg-secondary text-white transition-[width] duration-200 ease-out"
            :class="collapsed ? 'w-[74px]' : 'w-[228px]'"
        >
            <!-- a soft wash of the brand colour so the rail is not flat navy -->
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-primary/25 via-transparent to-primary/10" />

            <div class="relative flex items-center gap-3 px-5 py-6" :class="collapsed ? 'justify-center px-0' : ''">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-ui bg-primary font-bold">
                    L
                </div>
                <div v-if="!collapsed" class="min-w-0">
                    <p class="truncate text-sm font-bold">LittleSteps</p>
                    <p class="text-label text-white/50">Admin</p>
                </div>
            </div>

            <button
                type="button"
                class="absolute top-8 -right-3 z-10 flex h-6 w-6 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition-colors hover:text-primary-accessible"
                :title="collapsed ? 'Expand' : 'Collapse'"
                @click="toggleCollapsed"
            >
                <ChevronLeftIcon class="h-3.5 w-3.5 transition-transform" :class="collapsed ? 'rotate-180' : ''" />
            </button>

            <nav class="relative flex-1 overflow-y-auto px-3">
                <div v-for="(group, i) in groups" :key="i" class="mb-4">
                    <p
                        v-if="group.title && !collapsed"
                        class="mb-1 px-3 text-label font-semibold tracking-wide text-white/35 uppercase"
                    >
                        {{ group.title }}
                    </p>
                    <div v-else-if="group.title" class="mx-auto mb-2 h-px w-6 bg-white/10" />

                    <Link
                        v-for="item in group.items"
                        :key="item.href"
                        :href="item.href"
                        :title="collapsed ? item.label : undefined"
                        class="group relative mb-0.5 flex items-center gap-3 rounded-ui py-2 text-body font-medium transition-colors"
                        :class="[
                            collapsed ? 'justify-center px-0' : 'px-3',
                            isActive(item) ? 'bg-primary text-white' : 'text-white/65 hover:bg-white/10 hover:text-white',
                        ]"
                    >
                        <component :is="item.icon" class="h-[18px] w-[18px] flex-shrink-0" />
                        <span v-if="!collapsed">{{ item.label }}</span>

                        <span
                            v-if="collapsed"
                            class="pointer-events-none absolute left-full z-20 ml-2 hidden whitespace-nowrap rounded-ui bg-secondary px-2 py-1 text-label text-white shadow-lg group-hover:block"
                        >
                            {{ item.label }}
                        </span>
                    </Link>
                </div>
            </nav>

            <div class="relative flex-shrink-0 border-t border-white/10 px-3 py-4">
                <div class="flex items-center gap-3" :class="collapsed ? 'justify-center' : 'px-2'">
                    <div
                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary text-label font-bold"
                        :title="collapsed ? user?.name : undefined"
                    >
                        {{ initials }}
                    </div>
                    <div v-if="!collapsed" class="min-w-0 flex-1">
                        <p class="truncate text-body font-medium">{{ user?.name }}</p>
                        <p class="truncate text-label text-white/45">{{ user?.email }}</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="mt-3 flex w-full items-center gap-2 rounded-ui py-1.5 text-label text-white/50 transition-colors hover:bg-white/10 hover:text-white"
                    :class="collapsed ? 'justify-center px-0' : 'px-2'"
                    :title="collapsed ? 'Sign out' : undefined"
                    @click="logout"
                >
                    <ArrowRightStartOnRectangleIcon class="h-4 w-4 flex-shrink-0" />
                    <span v-if="!collapsed">Sign out</span>
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
