<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { countries } from '@/lib/countries';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const selectedCode = ref('+237');
const phoneNumber = ref('');

const form = useForm({
    name: user.name,
    email: user.email,
    phone: user.phone || '',
});

onMounted(() => {
    if (user.phone) {
        const country = countries.find(c => user.phone.startsWith(c.code.replace('+', '')));
        if (country) {
            selectedCode.value = country.code;
            phoneNumber.value = user.phone.substring(country.code.replace('+', '').length);
        } else {
            phoneNumber.value = user.phone;
        }
    }
});

const submit = () => {
    const index = selectedCode.value.replace('+', '');
    form.phone = index + phoneNumber.value.replace(/\s+/g, '');
    form.patch(route('profile.update'));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-foreground">
                {{ $t('profile.profileInformation') }}
            </h2>

            <p class="mt-1 text-sm text-muted-foreground">
                {{ $t('profile.profileInformationHelp') }}
            </p>
        </header>

        <form
            @submit.prevent="submit"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" :value="$t('auth.fullName')" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" :value="$t('auth.email')" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="phone" value="Numéro de téléphone" />

                <div class="flex mt-1">
                    <select
                        v-model="selectedCode"
                        class="rounded-l-md border border-input bg-background text-foreground shadow-sm focus:border-ring focus:ring-ring border-r-0"
                    >
                        <option v-for="country in countries" :key="country.code" :value="country.code">
                            {{ country.code }} ({{ country.name }})
                        </option>
                    </select>
                    <TextInput
                        id="phone"
                        type="text"
                        class="block w-full rounded-l-none"
                        v-model="phoneNumber"
                        placeholder="Ex: 6XXXXXXXX"
                    />
                </div>

                <InputError class="mt-2" :message="form.errors.phone" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-foreground">
                    {{ $t('profile.unverifiedEmail') }}
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-primary underline hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:ring-offset-background"
                    >
                        {{ $t('profile.resendVerificationLink') }}
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-emerald-600 dark:text-emerald-400"
                >
                    {{ $t('auth.verifyEmailSent') }}
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">{{ $t('common.save') }}</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-muted-foreground"
                    >
                        {{ $t('common.saved') }}
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
