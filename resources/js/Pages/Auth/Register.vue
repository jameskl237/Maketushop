<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/components/InputError.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Briefcase, Eye, EyeOff, Lock, Mail, MapPin, Phone, ShoppingBag, Sparkles, Store, User } from 'lucide-vue-next';
import { getCountries, getCountryCallingCode } from 'libphonenumber-js';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const buildPhoneCountryCodes = () => {
    try {
        const hasDisplayNames = typeof Intl !== 'undefined' && typeof Intl.DisplayNames === 'function';
        const regionNames = hasDisplayNames ? new Intl.DisplayNames(['fr'], { type: 'region' }) : null;
        const items = getCountries()
            .map((country) => ({
                country,
                name: regionNames?.of(country) ?? country,
                dialCode: getCountryCallingCode(country),
            }))
            .filter((item) => item?.dialCode)
            .sort((a, b) => String(a.name).localeCompare(String(b.name)));
        if (items.length) return items;
    } catch {}
    return [{ country: 'CM', name: 'Cameroun', dialCode: '237' }];
};

const phoneCountryCodes = buildPhoneCountryCodes();
const page = usePage();
const isGoogleOAuthConfigured = Boolean(page.props.auth?.google_oauth_configured ?? false);

// 'client' | 'vendeur' | 'prestataire' | 'both'
const accountType = ref(null);

const isPro = computed(() => ['vendeur', 'prestataire', 'both'].includes(accountType.value));

const accountTitle = computed(() => {
    switch (accountType.value) {
        case 'vendeur': return 'Compte vendeur';
        case 'prestataire': return 'Compte prestataire';
        case 'both': return 'Compte vendeur & prestataire';
        default: return 'Compte client';
    }
});

const accountSubtitle = computed(() =>
    isPro.value ? 'Remplissez vos informations professionnelles' : 'Quelques infos pour commencer',
);

const form = useForm({
    account_type: '',
    name: '',
    username: '',
    email: '',
    phone_country_code: '237',
    phone_number: '',
    address: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirm = ref(false);

const selectType = (type) => {
    accountType.value = type;
    form.account_type = type;
};

const googleRedirectHref = computed(() => {
    return accountType.value
        ? route('auth.google.redirect', { account_type: accountType.value })
        : route('auth.google.redirect');
});

const submit = () => {
    if (isPro.value) {
        form.phone_number = form.phone_number.replace(/\D/g, '');
    }
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const inputClass = 'h-11 w-full rounded-[14px] border border-border bg-shop-bg pl-10 pr-4 text-[13px] text-foreground placeholder:text-shop-light focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:bg-white/5';
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.register')" />

        <!-- Step 1: Choose account type -->
        <template v-if="!accountType">
            <div class="mb-6 text-center">
                <h1 class="font-display text-[20px] font-extrabold text-foreground">Créer un compte</h1>
                <p class="mt-1 text-[12px] text-shop-muted">Quel type de compte souhaitez-vous ?</p>
            </div>

            <div class="space-y-3">
                <!-- Client -->
                <button
                    type="button"
                    class="group flex w-full items-center gap-4 rounded-[18px] border-2 border-border bg-shop-bg p-4 text-left transition hover:border-primary hover:bg-primary/5 active:scale-[0.98] dark:bg-white/5"
                    @click="selectType('client')"
                >
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition">
                        <ShoppingBag class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[14px] font-bold text-foreground">Je suis client</p>
                        <p class="text-[11px] text-shop-muted">Achetez des produits sur la plateforme</p>
                    </div>
                    <svg class="ml-auto h-4 w-4 text-shop-light group-hover:text-primary transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <!-- Vendeur -->
                <button
                    type="button"
                    class="group flex w-full items-center gap-4 rounded-[18px] border-2 border-border bg-shop-bg p-4 text-left transition hover:border-orange hover:bg-orange/5 active:scale-[0.98] dark:bg-white/5"
                    @click="selectType('vendeur')"
                >
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-orange/10 text-orange group-hover:bg-orange group-hover:text-white transition">
                        <Store class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[14px] font-bold text-foreground">Je veux vendre des produits</p>
                        <p class="text-[11px] text-shop-muted">Créez votre boutique et vendez vos produits</p>
                    </div>
                    <svg class="ml-auto h-4 w-4 text-shop-light group-hover:text-orange transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <!-- Prestataire -->
                <button
                    type="button"
                    class="group flex w-full items-center gap-4 rounded-[18px] border-2 border-border bg-shop-bg p-4 text-left transition hover:border-orange hover:bg-orange/5 active:scale-[0.98] dark:bg-white/5"
                    @click="selectType('prestataire')"
                >
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-orange/10 text-orange group-hover:bg-orange group-hover:text-white transition">
                        <Briefcase class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[14px] font-bold text-foreground">Je propose des services</p>
                        <p class="text-[11px] text-shop-muted">Proposez vos prestations et recevez des demandes</p>
                    </div>
                    <svg class="ml-auto h-4 w-4 text-shop-light group-hover:text-orange transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <!-- Les deux -->
                <button
                    type="button"
                    class="group flex w-full items-center gap-4 rounded-[18px] border-2 border-border bg-shop-bg p-4 text-left transition hover:border-primary hover:bg-primary/5 active:scale-[0.98] dark:bg-white/5"
                    @click="selectType('both')"
                >
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition">
                        <Sparkles class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[14px] font-bold text-foreground">Les deux</p>
                        <p class="text-[11px] text-shop-muted">Vendez des produits ET proposez des services</p>
                    </div>
                    <svg class="ml-auto h-4 w-4 text-shop-light group-hover:text-primary transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <p class="mt-5 text-center text-[12px] text-shop-muted">
                Déjà un compte ?
                <Link :href="route('login')" class="font-semibold text-primary hover:underline">Se connecter</Link>
            </p>
        </template>

        <!-- Step 2: Fill form -->
        <template v-else>
            <!-- Header -->
            <div class="mb-5">
                <button type="button" class="mb-3 flex items-center gap-1.5 text-[11px] text-shop-muted transition hover:text-foreground" @click="accountType = null">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M19 12H5M5 12l7-7M5 12l7 7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Retour
                </button>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl" :class="isPro ? 'bg-orange/10 text-orange' : 'bg-primary/10 text-primary'">
                        <Store v-if="isPro" class="h-5 w-5" />
                        <ShoppingBag v-else class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="font-display text-[18px] font-extrabold text-foreground">
                            {{ accountTitle }}
                        </h1>
                        <p class="text-[11px] text-shop-muted">
                            {{ accountSubtitle }}
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-3.5">

                <!-- Client fields: name + email -->
                <!-- Supplier fields: name + username -->
                <div :class="isPro ? 'grid grid-cols-2 gap-3' : ''">
                    <div class="space-y-1">
                        <label for="name" class="text-[11px] font-semibold text-foreground">{{ t('auth.fullName') }}</label>
                        <div class="relative">
                            <User class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-shop-light" />
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                autofocus
                                autocomplete="name"
                                :placeholder="t('auth.exampleFullName')"
                                :class="inputClass"
                            />
                        </div>
                        <InputError :message="form.errors.name" />
                    </div>

                    <div v-if="isPro" class="space-y-1">
                        <label for="username" class="text-[11px] font-semibold text-foreground">{{ t('auth.username') }}</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] font-medium text-shop-light">@</span>
                            <input
                                id="username"
                                v-model="form.username"
                                type="text"
                                required
                                autocomplete="username"
                                :placeholder="t('auth.exampleUsername')"
                                :class="inputClass"
                            />
                        </div>
                        <InputError :message="form.errors.username" />
                    </div>
                </div>

                <!-- Email -->
                <div class="space-y-1">
                    <label for="email" class="text-[11px] font-semibold text-foreground">{{ t('auth.email') }}</label>
                    <div class="relative">
                        <Mail class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-shop-light" />
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="email"
                            :placeholder="t('auth.exampleEmail')"
                            :class="inputClass"
                        />
                    </div>
                    <InputError :message="form.errors.email" />
                </div>

                <!-- Phone (supplier only) -->
                <div v-if="isPro" class="space-y-1">
                    <label for="phone" class="text-[11px] font-semibold text-foreground">{{ t('auth.phone') }}</label>
                    <div class="grid grid-cols-[120px_1fr] gap-2">
                        <div class="relative">
                            <Phone class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-shop-light" />
                            <select
                                id="phone_country_code"
                                v-model="form.phone_country_code"
                                required
                                class="h-11 w-full appearance-none rounded-[14px] border border-border bg-shop-bg pl-9 pr-3 text-[12px] text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:bg-white/5"
                            >
                                <option v-for="item in phoneCountryCodes" :key="`${item.country}-${item.dialCode}`" :value="item.dialCode">
                                    {{ item.name }} +{{ item.dialCode }}
                                </option>
                            </select>
                        </div>
                        <input
                            id="phone"
                            v-model="form.phone_number"
                            type="tel"
                            required
                            autocomplete="tel-national"
                            inputmode="numeric"
                            :placeholder="t('auth.examplePhone')"
                            class="h-11 w-full rounded-[14px] border border-border bg-shop-bg px-4 text-[13px] text-foreground placeholder:text-shop-light focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:bg-white/5"
                        />
                    </div>
                    <InputError :message="form.errors.phone_country_code" />
                    <InputError :message="form.errors.phone_number" />
                </div>

                <!-- Address (supplier only) -->
                <div v-if="isPro" class="space-y-1">
                    <label for="address" class="text-[11px] font-semibold text-foreground">{{ t('auth.address') }}</label>
                    <div class="relative">
                        <MapPin class="absolute left-3 top-3 h-3.5 w-3.5 text-shop-light" />
                        <textarea
                            id="address"
                            v-model="form.address"
                            required
                            rows="2"
                            :placeholder="t('auth.exampleAddress')"
                            class="w-full rounded-[14px] border border-border bg-shop-bg pl-9 pr-4 pt-2.5 pb-2.5 text-[13px] text-foreground placeholder:text-shop-light focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:bg-white/5 resize-none"
                        />
                    </div>
                    <InputError :message="form.errors.address" />
                </div>

                <!-- Passwords -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label for="password" class="text-[11px] font-semibold text-foreground">{{ t('auth.password') }}</label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-shop-light" />
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                :class="inputClass + ' pr-9'"
                            />
                            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-shop-light" @click="showPassword = !showPassword">
                                <EyeOff v-if="showPassword" class="h-3.5 w-3.5" />
                                <Eye v-else class="h-3.5 w-3.5" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="space-y-1">
                        <label for="password_confirmation" class="text-[11px] font-semibold text-foreground">{{ t('auth.confirmPasswordLabel') }}</label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-shop-light" />
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showPasswordConfirm ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                :class="inputClass + ' pr-9'"
                            />
                            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-shop-light" @click="showPasswordConfirm = !showPasswordConfirm">
                                <EyeOff v-if="showPasswordConfirm" class="h-3.5 w-3.5" />
                                <Eye v-else class="h-3.5 w-3.5" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password_confirmation" />
                    </div>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="mt-1 h-11 w-full rounded-[14px] text-[13px] font-bold text-white shadow-sm transition active:scale-95 disabled:opacity-60"
                    :class="isPro ? 'bg-orange shadow-orange/30' : 'bg-primary shadow-primary/30'"
                >
                    <span v-if="form.processing" class="flex items-center justify-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Création...
                    </span>
                    <span v-else>
                        {{ isPro ? t('auth.createSupplierAccountButton') : 'Créer mon compte' }}
                    </span>
                </button>

                <!-- Google -->
                <div v-if="isGoogleOAuthConfigured">
                    <div class="relative my-2 flex items-center">
                        <div class="flex-1 border-t border-border" />
                        <span class="mx-3 text-[11px] text-shop-light">ou</span>
                        <div class="flex-1 border-t border-border" />
                    </div>
                    <a
                        :href="googleRedirectHref"
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

                <p class="text-center text-[12px] text-shop-muted">
                    Déjà un compte ?
                    <Link :href="route('login')" class="font-semibold text-primary hover:underline">Se connecter</Link>
                </p>
            </form>
        </template>
    </GuestLayout>
</template>
