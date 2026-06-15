<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/components/InputError.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { Eye, EyeOff, Lock, Mail } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const page = usePage();
const isGoogleOAuthConfigured = Boolean(page.props.auth?.google_oauth_configured ?? false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const urlParams = new URLSearchParams(window.location.search);
const redirectTo = urlParams.get('redirect') || null;

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onSuccess: () => {
            if (redirectTo) router.visit(redirectTo);
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.login')" />

        <!-- Header -->
        <div class="mb-6 text-center">
            <h1 class="font-display text-[22px] font-extrabold text-foreground">{{ t('auth.login') }}</h1>
            <p class="mt-1 text-[12px] text-shop-muted">{{ t('auth.loginSubtitle', 'Ravi de vous revoir !') }}</p>
        </div>

        <div v-if="status" class="mb-4 rounded-[12px] bg-shop-green/10 px-3 py-2 text-[12px] font-medium text-shop-green">
            {{ status }}
        </div>
        <div v-if="$page.props.errors?.google" class="mb-4 rounded-[12px] bg-pink/10 px-3 py-2 text-[12px] font-medium text-pink">
            {{ $page.props.errors.google }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Email -->
            <div class="space-y-1.5">
                <label for="email" class="text-[12px] font-semibold text-foreground">{{ t('auth.email') }}</label>
                <div class="relative">
                    <Mail class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-shop-light" />
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        :placeholder="t('auth.exampleEmail', 'vous@exemple.com')"
                        class="h-11 w-full rounded-[14px] border border-border bg-shop-bg pl-10 pr-4 text-[13px] text-foreground placeholder:text-shop-light focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:bg-white/5"
                    />
                </div>
                <InputError :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <label for="password" class="text-[12px] font-semibold text-foreground">{{ t('auth.password') }}</label>
                <div class="relative">
                    <Lock class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-shop-light" />
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        :placeholder="t('auth.passwordPlaceholder', '••••••••')"
                        class="h-11 w-full rounded-[14px] border border-border bg-shop-bg pl-10 pr-10 text-[13px] text-foreground placeholder:text-shop-light focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:bg-white/5"
                    />
                    <button
                        type="button"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-shop-light transition hover:text-foreground"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" class="h-4 w-4" />
                        <Eye v-else class="h-4 w-4" />
                    </button>
                </div>
                <InputError :message="form.errors.password" />
            </div>

            <!-- Remember + Forgot -->
            <div class="flex items-center justify-between">
                <label class="flex cursor-pointer items-center gap-2">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="h-4 w-4 rounded-md border-border text-primary focus:ring-primary"
                    />
                    <span class="text-[11px] text-shop-muted">{{ t('auth.rememberMe') }}</span>
                </label>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-[11px] font-medium text-primary transition hover:underline"
                >
                    {{ t('auth.forgotPasswordQuestion') }}
                </Link>
            </div>

            <!-- Submit -->
            <button
                type="submit"
                :disabled="form.processing"
                class="h-11 w-full rounded-[14px] bg-primary text-[13px] font-bold text-white shadow-sm shadow-primary/30 transition active:scale-95 disabled:opacity-60"
            >
                <span v-if="form.processing" class="flex items-center justify-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ t('auth.loading', 'Connexion...') }}
                </span>
                <span v-else>{{ t('auth.login') }}</span>
            </button>

            <!-- Google -->
            <div v-if="isGoogleOAuthConfigured">
                <div class="relative my-3 flex items-center">
                    <div class="flex-1 border-t border-border" />
                    <span class="mx-3 text-[11px] text-shop-light">ou</span>
                    <div class="flex-1 border-t border-border" />
                </div>
                <a
                    :href="route('auth.google.redirect') + (redirectTo ? '?redirect=' + encodeURIComponent(redirectTo) : '')"
                    class="flex h-11 w-full items-center justify-center gap-2.5 rounded-[14px] border border-border bg-white text-[13px] font-medium text-foreground transition hover:bg-shop-bg active:scale-95 dark:bg-white/5"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.3-1.5 3.9-5.5 3.9-3.3 0-6-2.8-6-6.1s2.7-6.1 6-6.1c1.9 0 3.2.8 3.9 1.5l2.6-2.5C16.9 3.4 14.7 2.4 12 2.4 6.9 2.4 2.8 6.5 2.8 11.8S6.9 21.2 12 21.2c6.9 0 9.1-4.8 9.1-7.3 0-.5-.1-.9-.1-1.2H12z"/>
                        <path fill="#34A853" d="M3.7 7.6l3.2 2.4c.9-2 2.8-3.4 5.1-3.4 1.9 0 3.2.8 3.9 1.5l2.6-2.5C16.9 3.4 14.7 2.4 12 2.4c-3.6 0-6.8 2.1-8.3 5.2z"/>
                        <path fill="#4A90E2" d="M12 21.2c2.7 0 5-0.9 6.7-2.5l-3.1-2.5c-.8.6-2 1.1-3.6 1.1-3.9 0-5.2-2.6-5.5-3.9l-3.2 2.4c1.5 3.1 4.7 5.4 8.7 5.4z"/>
                        <path fill="#FBBC05" d="M3.3 15.8l3.2-2.4c-.2-.6-.3-1.1-.3-1.7s.1-1.2.3-1.7L3.3 7.6c-.6 1.2-1 2.7-1 4.1s.4 2.9 1 4.1z"/>
                    </svg>
                    {{ t('auth.googleLogin') }}
                </a>
            </div>

            <!-- Register button -->
            <div class="space-y-2">
                <div class="relative flex items-center">
                    <div class="flex-1 border-t border-border" />
                    <span class="mx-3 text-[11px] text-shop-light">{{ t('auth.noAccount', "Pas encore de compte ?") }}</span>
                    <div class="flex-1 border-t border-border" />
                </div>
                <Link :href="route('register')" class="flex h-11 w-full items-center justify-center rounded-[14px] border-2 border-primary text-[13px] font-bold text-primary transition hover:bg-primary/5 active:scale-95">
                    {{ t('auth.signUp', "Créer un compte") }}
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
