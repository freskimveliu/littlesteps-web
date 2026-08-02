<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import UserShell from '../../../components/users/UserShell.vue';
import UiCard from '../../../components/ui/UiCard.vue';
import UiInput from '../../../components/ui/UiInput.vue';
import UiSwitch from '../../../components/ui/UiSwitch.vue';
import UiButton from '../../../components/ui/UiButton.vue';
import UiBadge from '../../../components/ui/UiBadge.vue';
import type { UserSummary } from '../../../types/admin';
import { formatDate } from '../../../support/date';

const props = defineProps<{
    user: UserSummary;
    devices: { id: number; platform: string; device_id: string | null; created_at: string }[];
    settings: Record<string, boolean>;
}>();

const form = useForm({
    name: props.user.name,
    email: props.user.email ?? '',
    timezone: props.user.timezone,
    is_admin: props.user.is_admin,
});

function submit() {
    form.put(`/admin/users/${props.user.id}`);
}
</script>

<template>
    <Head :title="`${user.name} · Profile`" />

    <UserShell :user="user" tab="profile">
        <div class="grid gap-6 lg:grid-cols-2">
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
                            v-for="(value, key) in settings"
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
    </UserShell>
</template>
