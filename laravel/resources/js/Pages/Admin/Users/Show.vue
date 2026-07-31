<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import UiPageHeader from '../../../components/ui/UiPageHeader.vue';
import UiCard from '../../../components/ui/UiCard.vue';
import UiInput from '../../../components/ui/UiInput.vue';
import UiSelect from '../../../components/ui/UiSelect.vue';
import UiSwitch from '../../../components/ui/UiSwitch.vue';
import UiButton from '../../../components/ui/UiButton.vue';
import UiBadge from '../../../components/ui/UiBadge.vue';
import UiTable from '../../../components/ui/UiTable.vue';
import UiTableRow from '../../../components/ui/UiTableRow.vue';
import UiTableCell from '../../../components/ui/UiTableCell.vue';
import UiTableHeader from '../../../components/ui/UiTableHeader.vue';

interface ChildRow {
    id: number;
    name: string;
    birthday: string;
    age_months: number;
    gender: string;
    xp: number;
    entries_count: number;
    is_owner: boolean;
    role: string;
    relation: string;
}

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string | null;
        language: string;
        timezone: string;
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
    children: ChildRow[];
    devices: { id: number; platform: string; device_id: string | null; created_at: string }[];
    written: number;
    languages: string[];
}>();

const form = useForm({
    name: props.user.name,
    email: props.user.email ?? '',
    language: props.user.language,
    timezone: props.user.timezone,
    is_admin: props.user.is_admin,
});

function submit() {
    form.put(`/admin/users/${props.user.id}`);
}

function restore() {
    router.post(`/admin/users/${props.user.id}/restore`);
}

function age(months: number): string {
    if (months < 24) return `${months} mo`;
    return `${Math.floor(months / 12)} yr`;
}
</script>

<template>
    <Head :title="user.name" />

    <AdminLayout>
        <UiPageHeader :title="user.name" back-to="/admin/users" back-label="Parents">
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
                <p class="text-2xl font-bold text-slate-900">{{ user.current_streak }}</p>
                <p class="text-body text-slate-500">Current streak</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ user.longest_streak }}</p>
                <p class="text-body text-slate-500">Best streak</p>
            </UiCard>
            <UiCard body-class="px-5 py-4">
                <p class="text-2xl font-bold text-slate-900">{{ devices.length }}</p>
                <p class="text-body text-slate-500">Devices</p>
            </UiCard>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <UiCard title="Children" flush>
                    <UiTable bare :empty="children.length === 0" empty-title="No children yet">
                        <template #header>
                            <UiTableHeader>Name</UiTableHeader>
                            <UiTableHeader>Relation</UiTableHeader>
                            <UiTableHeader align="right">Age</UiTableHeader>
                            <UiTableHeader align="right">XP</UiTableHeader>
                            <UiTableHeader align="right">Memories</UiTableHeader>
                            <UiTableHeader align="right" />
                        </template>
                        <template #body>
                            <UiTableRow v-for="child in children" :key="child.id">
                                <UiTableCell>
                                    <Link
                                        :href="`/admin/children/${child.id}`"
                                        class="font-medium text-slate-900 hover:underline"
                                    >
                                        {{ child.name }}
                                    </Link>
                                </UiTableCell>
                                <UiTableCell>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-slate-600">{{ child.relation }}</span>
                                        <UiBadge :tone="child.is_owner ? 'primary' : 'neutral'">
                                            {{ child.is_owner ? 'creator' : child.role }}
                                        </UiBadge>
                                    </div>
                                </UiTableCell>
                                <UiTableCell align="right">{{ age(child.age_months) }}</UiTableCell>
                                <UiTableCell align="right">{{ child.xp }}</UiTableCell>
                                <UiTableCell align="right">{{ child.entries_count }}</UiTableCell>
                                <UiTableCell align="right">
                                    <UiButton variant="outline" :to="`/admin/children/${child.id}`">Open</UiButton>
                                </UiTableCell>
                            </UiTableRow>
                        </template>
                    </UiTable>
                </UiCard>

                <div class="mt-6">
                    <UiCard title="Devices" flush>
                        <UiTable bare :empty="devices.length === 0" empty-title="No devices registered">
                            <template #header>
                                <UiTableHeader>Platform</UiTableHeader>
                                <UiTableHeader>Device</UiTableHeader>
                                <UiTableHeader align="right">Registered</UiTableHeader>
                            </template>
                            <template #body>
                                <UiTableRow v-for="device in devices" :key="device.id">
                                    <UiTableCell>{{ device.platform }}</UiTableCell>
                                    <UiTableCell cell-class="text-slate-500">{{ device.device_id ?? '—' }}</UiTableCell>
                                    <UiTableCell align="right" cell-class="text-slate-500">
                                        {{ device.created_at?.slice(0, 10) }}
                                    </UiTableCell>
                                </UiTableRow>
                            </template>
                        </UiTable>
                    </UiCard>
                </div>
            </div>

            <div>
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
                        <UiSelect
                            v-model="form.language"
                            label="Language"
                            required
                            :error="form.errors.language"
                            :options="languages.map((l) => ({ value: l, label: l }))"
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

                <div class="mt-6">
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
        </div>
    </AdminLayout>
</template>
