<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import UiPageHeader from '../../../components/ui/UiPageHeader.vue';
import UiCard from '../../../components/ui/UiCard.vue';
import UiInput from '../../../components/ui/UiInput.vue';
import UiButton from '../../../components/ui/UiButton.vue';

interface Setting {
    key: string;
    value: number;
    default: number;
    hint: string | null;
}

const props = defineProps<{ settings: Setting[] }>();

const form = useForm({
    settings: Object.fromEntries(props.settings.map((s) => [s.key, s.value])) as Record<string, number>,
});

function label(key: string): string {
    return key.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase());
}

function submit() {
    form.put('/admin/settings');
}
</script>

<template>
    <Head title="Settings" />

    <AdminLayout>
        <UiPageHeader title="Settings" />

        <div class="max-w-2xl">
            <UiCard>
                <form class="flex flex-col gap-5" @submit.prevent="submit">
                    <div v-for="setting in settings" :key="setting.key">
                        <UiInput
                            v-model="form.settings[setting.key]"
                            type="number"
                            :label="label(setting.key)"
                            :hint="setting.hint ?? undefined"
                            :error="(form.errors as Record<string, string>)[`settings.${setting.key}`]"
                        />
                        <p v-if="setting.value !== setting.default" class="mt-1 text-label text-amber-600">
                            Default is {{ setting.default }}
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <UiButton type="submit" :loading="form.processing">Submit</UiButton>
                    </div>
                </form>
            </UiCard>
        </div>
    </AdminLayout>
</template>
