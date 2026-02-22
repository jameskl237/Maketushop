<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="$t('auth.login')" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>
        <div v-if="$page.props.errors?.google" class="mb-4 text-sm font-medium text-destructive">
            {{ $page.props.errors.google }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" :value="$t('auth.email')" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" :value="$t('auth.password')" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-muted-foreground"
                        >{{ $t('auth.rememberMe') }}</span
                    >
                </label>
            </div>

            <div class="mt-4 flex flex-col-reverse items-start gap-3 sm:flex-row sm:items-center sm:justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-muted-foreground underline hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                >
                    {{ $t('auth.forgotPasswordQuestion') }}
                </Link>

                <PrimaryButton
                    class="w-full sm:ms-4 sm:w-auto"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ $t('auth.login') }}
                </PrimaryButton>
            </div>
            <div class="mt-3">
                <a
                    :href="route('auth.google.redirect')"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-border bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm transition hover:bg-muted"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.3-1.5 3.9-5.5 3.9-3.3 0-6-2.8-6-6.1s2.7-6.1 6-6.1c1.9 0 3.2.8 3.9 1.5l2.6-2.5C16.9 3.4 14.7 2.4 12 2.4 6.9 2.4 2.8 6.5 2.8 11.8S6.9 21.2 12 21.2c6.9 0 9.1-4.8 9.1-7.3 0-.5-.1-.9-.1-1.2H12z"/>
                        <path fill="#34A853" d="M3.7 7.6l3.2 2.4c.9-2 2.8-3.4 5.1-3.4 1.9 0 3.2.8 3.9 1.5l2.6-2.5C16.9 3.4 14.7 2.4 12 2.4c-3.6 0-6.8 2.1-8.3 5.2z"/>
                        <path fill="#4A90E2" d="M12 21.2c2.7 0 5-0.9 6.7-2.5l-3.1-2.5c-.8.6-2 1.1-3.6 1.1-3.9 0-5.2-2.6-5.5-3.9l-3.2 2.4c1.5 3.1 4.7 5.4 8.7 5.4z"/>
                        <path fill="#FBBC05" d="M3.3 15.8l3.2-2.4c-.2-.6-.3-1.1-.3-1.7s.1-1.2.3-1.7L3.3 7.6c-.6 1.2-1 2.7-1 4.1s.4 2.9 1 4.1z"/>
                    </svg>
                    {{ $t('auth.googleLogin') }}
                </a>
            </div>
        </form>
    </GuestLayout>
</template>
