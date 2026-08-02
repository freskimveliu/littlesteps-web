<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '../../layouts/AdminLayout.vue';
import UiPageHeader from '../ui/UiPageHeader.vue';
import UiCard from '../ui/UiCard.vue';
import UiBadge from '../ui/UiBadge.vue';
import UiButton from '../ui/UiButton.vue';
import type { UserSummary, UserTab } from '../../types/admin';

const props = defineProps<{ user: UserSummary; tab: UserTab }>();

const tabs = computed(() => [
    { key: 'overview', label: 'Overview', href: `/admin/users/${props.user.id}` },
    {
        key: 'children',
        label: `Children (${props.user.children_count})`,
        href: `/admin/users/${props.user.id}/children`,
    },
    { key: 'profile', label: 'Profile', href: `/admin/users/${props.user.id}/profile` },
]);

function restore() {
    router.post(`/admin/users/${props.user.id}/restore`);
}
</script>

<template>
    <AdminLayout>
        <UiPageHeader :title="user.name" back-to="/admin/users" back-label="Users">
            <template #actions>
                <UiBadge v-if="user.is_admin" tone="primary">admin</UiBadge>
                <UiBadge v-if="!user.is_registered" tone="gold">not signed up</UiBadge>
                <UiButton v-if="user.deleted_at" variant="outline" @click="restore">Restore account</UiButton>
            </template>
        </UiPageHeader>

        <div
            v-if="user.deleted_at"
            class="mb-6 rounded-ui border border-red-200 bg-red-50 px-4 py-3 text-body text-red-700"
        >
            This account is scheduled for deletion. Nothing has been destroyed yet — restoring brings the children
            and every memory back with it.
        </div>

        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ user.children_count }}</p>
                <p class="text-body text-slate-500">Children</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ user.written }}</p>
                <p class="text-body text-slate-500">Memories written</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ user.photos }}</p>
                <p class="text-body text-slate-500">Photos</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ user.current_streak }}</p>
                <p class="text-body text-slate-500">Current streak</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ user.longest_streak }}</p>
                <p class="text-body text-slate-500">Best streak</p>
            </UiCard>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <UiButton v-for="t in tabs" :key="t.key" variant="outline" :active="tab === t.key" :to="t.href">
                {{ t.label }}
            </UiButton>
        </div>

        <slot />
    </AdminLayout>
</template>
