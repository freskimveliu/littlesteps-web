<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import UiCard from '../../../components/ui/UiCard.vue';
import UiInput from '../../../components/ui/UiInput.vue';
import UiButton from '../../../components/ui/UiButton.vue';

const form = useForm({ email: '', password: '', remember: false });

function submit() {
    form.post('/admin/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <Head title="Admin sign in" />

    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-sm">
            <div class="mb-6 text-center">
                <p class="text-xl font-bold text-slate-900">LittleSteps</p>
                <p class="text-body text-slate-500">Admin</p>
            </div>

            <UiCard>
                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <UiInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        label="Email"
                        autocomplete="email"
                        required
                        :error="form.errors.email"
                    />
                    <UiInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        label="Password"
                        autocomplete="current-password"
                        required
                        :error="form.errors.password"
                    />

                    <label class="flex items-center gap-2 text-body text-slate-600">
                        <input v-model="form.remember" type="checkbox" class="rounded border-slate-300" />
                        Keep me signed in
                    </label>

                    <UiButton type="submit" full-width :loading="form.processing">Submit</UiButton>
                </form>
            </UiCard>
        </div>
    </div>
</template>
