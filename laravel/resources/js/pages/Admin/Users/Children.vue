<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import UserShell from '../../../components/users/UserShell.vue';
import UiCard from '../../../components/ui/UiCard.vue';
import UiAvatar from '../../../components/ui/UiAvatar.vue';
import UiBadge from '../../../components/ui/UiBadge.vue';
import UiButton from '../../../components/ui/UiButton.vue';
import UiEmptyState from '../../../components/ui/UiEmptyState.vue';
import type { UserSummary } from '../../../types/admin';
import { formatDate } from '../../../support/date';

interface ChildCard {
    id: number;
    name: string;
    birthday: string;
    age_months: number;
    gender: string;
    xp: number;
    photo: string | null;
    level: number;
    level_name: string;
    level_progress: number;
    xp_to_next: number | null;
    entries_count: number;
    trophies_count: number;
    chapters_done_count: number;
    written_here: number;
    is_owner: boolean;
    role: string;
    relation: string;
}

defineProps<{
    user: UserSummary;
    children: ChildCard[];
    chapterCount: number;
}>();

function age(months: number): string {
    if (months < 24) return `${months} mo`;
    return `${Math.floor(months / 12)} yr ${months % 12} mo`;
}
</script>

<template>
    <Head :title="`${user.name} · Children`" />

    <UserShell :user="user" tab="children">
        <UiCard v-if="!children.length">
            <UiEmptyState title="No children yet" :description="`${user.name} has not added a child to this account.`" />
        </UiCard>

        <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <UiCard v-for="child in children" :key="child.id" body-class="p-5">
                <div class="flex items-center gap-3">
                    <UiAvatar :src="child.photo" :name="child.name" />

                    <div class="min-w-0 flex-1">
                        <Link
                            :href="`/admin/children/${child.id}`"
                            class="text-card-title font-bold text-slate-900 hover:underline"
                        >
                            {{ child.name }}
                        </Link>
                        <p class="text-label text-slate-400">
                            {{ child.gender }} · {{ age(child.age_months) }} · born
                            {{ formatDate(child.birthday) }}
                        </p>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    <UiBadge :tone="child.is_owner ? 'primary' : 'neutral'">
                        {{ child.is_owner ? 'creator' : child.role }}
                    </UiBadge>
                    <UiBadge tone="neutral">{{ child.relation }}</UiBadge>
                </div>

                <div class="mt-4">
                    <div class="mb-1.5 flex items-baseline justify-between gap-2 text-body">
                        <span class="font-medium text-slate-800">Level {{ child.level }} · {{ child.level_name }}</span>
                        <span class="text-slate-400">{{ child.xp }} XP</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-primary"
                            :style="{ width: `${child.level_progress * 100}%` }"
                        />
                    </div>
                    <p class="mt-1.5 text-label text-slate-400">
                        {{
                            child.xp_to_next === null
                                ? 'Top of the ladder'
                                : `${child.xp_to_next} XP to the next level`
                        }}
                    </p>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-2 border-t border-[#f0f4f8] pt-4 text-center">
                    <div>
                        <p class="text-body font-bold text-slate-900">{{ child.entries_count }}</p>
                        <p class="text-label text-slate-400">Memories</p>
                    </div>
                    <div>
                        <p class="text-body font-bold text-slate-900">{{ child.trophies_count }}</p>
                        <p class="text-label text-slate-400">Trophies</p>
                    </div>
                    <div>
                        <p class="text-body font-bold text-slate-900">
                            {{ child.chapters_done_count }} / {{ chapterCount }}
                        </p>
                        <p class="text-label text-slate-400">Chapters</p>
                    </div>
                </div>

                <div class="mt-4">
                    <UiButton variant="outline" full-width :to="`/admin/children/${child.id}`">
                        Open {{ child.name }}
                    </UiButton>
                </div>
            </UiCard>
        </div>
    </UserShell>
</template>
