<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import UiPageHeader from '../../../components/ui/UiPageHeader.vue';
import UiCard from '../../../components/ui/UiCard.vue';
import UiInput from '../../../components/ui/UiInput.vue';
import UiSwitch from '../../../components/ui/UiSwitch.vue';
import UiButton from '../../../components/ui/UiButton.vue';
import UiBadge from '../../../components/ui/UiBadge.vue';
import UiBarChart from '../../../components/ui/UiBarChart.vue';
import UiSplitBar from '../../../components/ui/UiSplitBar.vue';
import UiEmptyState from '../../../components/ui/UiEmptyState.vue';
import UiTable from '../../../components/ui/UiTable.vue';
import UiTableRow from '../../../components/ui/UiTableRow.vue';
import UiTableCell from '../../../components/ui/UiTableCell.vue';
import UiTableHeader from '../../../components/ui/UiTableHeader.vue';
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

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string | null;
        timezone: string;
        language: string | null;
        is_admin: boolean;
        is_registered: boolean;
        current_streak: number;
        longest_streak: number;
        last_entry_date: string | null;
        deleted_at: string | null;
        created_at: string | null;
        photo: string | null;
        settings: Record<string, boolean>;
    };
    children: ChildCard[];
    devices: { id: number; platform: string; device_id: string | null; created_at: string }[];
    written: number;
    photos: number;
    chapterCount: number;
    activity: { label: string; value: number }[];
    memoryMix: { label: string; value: number; color: string }[];
    recent: {
        id: number;
        child: { id: number; name: string } | null;
        milestone: string | null;
        description: string | null;
        date: string;
        mood: string | null;
        photos: number;
    }[];
}>();

const tab = ref<'overview' | 'children' | 'profile'>('overview');

const tabs = computed(
    () =>
        [
            { key: 'overview', label: 'Overview' },
            { key: 'children', label: `Children (${props.children.length})` },
            { key: 'profile', label: 'Profile' },
        ] as const,
);

const form = useForm({
    name: props.user.name,
    email: props.user.email ?? '',
    timezone: props.user.timezone,
    is_admin: props.user.is_admin,
});

const busiest = computed(() => Math.max(1, ...props.children.map((child) => child.written_here)));

const facts = computed(() => [
    { label: 'Status', value: props.user.is_registered ? 'Signed up' : 'Guest account' },
    { label: 'Joined', value: formatDate(props.user.created_at) },
    { label: 'Last memory', value: formatDate(props.user.last_entry_date) },
    { label: 'Photos uploaded', value: String(props.photos) },
    { label: 'Language', value: props.user.language ?? '—' },
    { label: 'Timezone', value: props.user.timezone },
    { label: 'Devices', value: String(props.devices.length) },
]);

function submit() {
    form.put(`/admin/users/${props.user.id}`);
}

function restore() {
    router.post(`/admin/users/${props.user.id}/restore`);
}

function age(months: number): string {
    if (months < 24) return `${months} mo`;
    return `${Math.floor(months / 12)} yr ${months % 12} mo`;
}

function initial(name: string): string {
    return name.trim().charAt(0).toUpperCase();
}
</script>

<template>
    <Head :title="user.name" />

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
                <p class="text-2xl font-bold text-slate-900">{{ children.length }}</p>
                <p class="text-body text-slate-500">Children</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ written }}</p>
                <p class="text-body text-slate-500">Memories written</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ photos }}</p>
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
            <UiButton v-for="t in tabs" :key="t.key" variant="outline" :active="tab === t.key" @click="tab = t.key">
                {{ t.label }}
            </UiButton>
        </div>

        <template v-if="tab === 'overview'">
            <div class="mb-4 grid gap-4 lg:grid-cols-3">
                <UiCard title="Memories captured" class="lg:col-span-2">
                    <p class="mb-4 text-body text-slate-500">
                        Every memory this account wrote, by the week the memory belongs to.
                    </p>
                    <UiBarChart :points="activity" :label-every="3" />
                </UiCard>

                <UiCard title="Account">
                    <div class="flex flex-col gap-2.5">
                        <div v-for="fact in facts" :key="fact.label" class="flex items-baseline justify-between gap-3">
                            <span class="text-body text-slate-500">{{ fact.label }}</span>
                            <span class="text-body font-medium text-slate-800">{{ fact.value }}</span>
                        </div>
                    </div>
                </UiCard>
            </div>

            <div class="mb-4 grid gap-4 lg:grid-cols-2">
                <UiCard title="Where their memories come from">
                    <p class="mb-4 text-body text-slate-500">
                        A milestone memory is worth its own XP; a free one is capped at one a day.
                    </p>
                    <UiSplitBar :slices="memoryMix" />
                </UiCard>

                <UiCard title="Who they write about">
                    <p class="mb-4 text-body text-slate-500">Memories written by this account, per child.</p>

                    <div v-if="children.length" class="flex flex-col gap-3">
                        <div v-for="child in children" :key="child.id">
                            <div class="mb-1 flex items-baseline justify-between gap-3 text-body">
                                <span class="font-medium text-slate-800">{{ child.name }}</span>
                                <span class="text-slate-400">{{ child.written_here }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full bg-primary/70"
                                    :style="{ width: `${(child.written_here / busiest) * 100}%` }"
                                />
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-body text-slate-400">No children on this account yet.</p>
                </UiCard>
            </div>

            <UiCard title="Latest memories" flush>
                <UiTable
                    bare
                    :empty="recent.length === 0"
                    empty-title="Nothing written yet"
                    :empty-description="`${user.name} has not recorded a memory.`"
                >
                    <template #header>
                        <UiTableHeader>Date</UiTableHeader>
                        <UiTableHeader>Child</UiTableHeader>
                        <UiTableHeader>Milestone</UiTableHeader>
                        <UiTableHeader>Story</UiTableHeader>
                        <UiTableHeader align="right">Photos</UiTableHeader>
                    </template>
                    <template #body>
                        <UiTableRow v-for="entry in recent" :key="entry.id">
                            <UiTableCell cell-class="whitespace-nowrap text-slate-500">
                                {{ formatDate(entry.date) }}
                            </UiTableCell>
                            <UiTableCell>
                                <Link
                                    v-if="entry.child"
                                    :href="`/admin/children/${entry.child.id}`"
                                    class="font-medium text-slate-900 hover:underline"
                                >
                                    {{ entry.child.name }}
                                </Link>
                            </UiTableCell>
                            <UiTableCell>
                                <span v-if="entry.milestone" class="text-slate-800">{{ entry.milestone }}</span>
                                <UiBadge v-else tone="neutral">free</UiBadge>
                            </UiTableCell>
                            <UiTableCell cell-class="max-w-md">
                                <span class="text-slate-700">{{ entry.description ?? '—' }}</span>
                                <UiBadge v-if="entry.mood" tone="primary" class="ml-2">{{ entry.mood }}</UiBadge>
                            </UiTableCell>
                            <UiTableCell align="right">{{ entry.photos || '—' }}</UiTableCell>
                        </UiTableRow>
                    </template>
                </UiTable>
            </UiCard>
        </template>

        <template v-else-if="tab === 'children'">
            <UiCard v-if="!children.length">
                <UiEmptyState
                    title="No children yet"
                    :description="`${user.name} has not added a child to this account.`"
                />
            </UiCard>

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <UiCard v-for="child in children" :key="child.id" body-class="p-5">
                    <div class="flex items-center gap-3">
                        <img
                            v-if="child.photo"
                            :src="child.photo"
                            :alt="child.name"
                            class="h-12 w-12 rounded-full object-cover"
                        />
                        <span
                            v-else
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 font-display text-lg font-bold text-primary-accessible"
                        >
                            {{ initial(child.name) }}
                        </span>

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
                            <span class="font-medium text-slate-800">
                                Level {{ child.level }} · {{ child.level_name }}
                            </span>
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
        </template>

        <div v-else class="grid gap-6 lg:grid-cols-2">
            <UiCard title="Profile">
                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <UiInput v-model="form.name" label="Name" required :error="form.errors.name" />
                    <UiInput
                        v-model="form.email"
                        type="email"
                        label="Email"
                        hint="Empty until they sign up"
                        :error="form.errors.email"
                    />
                    <UiInput
                        v-model="form.timezone"
                        label="Timezone"
                        required
                        hint="Decides when their day rolls over"
                        :error="form.errors.timezone"
                    />
                    <UiSwitch v-model="form.is_admin" label="Can reach this admin" />

                    <div class="flex justify-end">
                        <UiButton type="submit" :loading="form.processing">Submit</UiButton>
                    </div>
                </form>
            </UiCard>

            <div class="flex flex-col gap-6">
                <UiCard title="Devices">
                    <div v-if="devices.length" class="flex flex-col gap-3">
                        <div
                            v-for="device in devices"
                            :key="device.id"
                            class="flex items-start justify-between gap-3 text-body"
                        >
                            <div class="min-w-0">
                                <p class="text-slate-600">{{ device.platform }}</p>
                                <p v-if="device.device_id" class="truncate text-label text-slate-400">
                                    {{ device.device_id }}
                                </p>
                            </div>
                            <span class="shrink-0 text-slate-400">{{ formatDate(device.created_at) }}</span>
                        </div>
                    </div>
                    <p v-else class="text-body text-slate-400">No devices registered</p>
                </UiCard>

                <UiCard title="Notifications">
                    <div class="flex flex-col gap-2">
                        <div
                            v-for="(value, key) in user.settings"
                            :key="key"
                            class="flex items-center justify-between text-body"
                        >
                            <span class="text-slate-600">{{ String(key).replace(/_/g, ' ') }}</span>
                            <UiBadge :tone="value ? 'success' : 'neutral'">{{ value ? 'on' : 'off' }}</UiBadge>
                        </div>
                    </div>
                </UiCard>
            </div>
        </div>
    </AdminLayout>
</template>
