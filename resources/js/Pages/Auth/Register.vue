<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { getCountries, getCountryCallingCode } from 'libphonenumber-js';

const form = useForm({
    name: '',
    username: '',
    email: '',
    phone_country_code: '237',
    phone_number: '',
    address: '',
    password: '',
    password_confirmation: '',
});

const regionNames = new Intl.DisplayNames(['fr'], { type: 'region' });

const phoneCountryCodes = getCountries()
    .map((country) => ({
        country,
        name: regionNames.of(country) ?? country,
        dialCode: getCountryCallingCode(country),
    }))
    .sort((a, b) => a.name.localeCompare(b.name));

const submit = () => {
    form.phone_number = form.phone_number.replace(/\D/g, '');

    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="$t('auth.supplierSignUp')" />

        <Card class="w-full border-border/70 bg-card/95 shadow-xl backdrop-blur">
            <CardHeader class="space-y-3">
                <div class="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <CardTitle class="text-xl sm:text-2xl">{{ $t('auth.createSupplierAccount') }}</CardTitle>
                    <Badge variant="secondary">{{ $t('auth.supplierOnly') }}</Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ $t('auth.supplierRegisterHelp') }}
                </p>
            </CardHeader>

            <CardContent>
                <form class="space-y-5" @submit.prevent="submit">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name">{{ $t('auth.fullName') }}</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                autofocus
                                autocomplete="name"
                                :placeholder="$t('auth.exampleFullName')"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="username">{{ $t('auth.username') }}</Label>
                            <Input
                                id="username"
                                v-model="form.username"
                                type="text"
                                required
                                autocomplete="username"
                                :placeholder="$t('auth.exampleUsername')"
                            />
                            <InputError :message="form.errors.username" />
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="email">{{ $t('auth.email') }}</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autocomplete="email"
                                :placeholder="$t('auth.exampleEmail')"
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="phone">{{ $t('auth.phone') }}</Label>
                            <div class="grid grid-cols-[130px_1fr] gap-2">
                                <select
                                    id="phone_country_code"
                                    v-model="form.phone_country_code"
                                    class="h-10 rounded-md border border-input bg-background px-2 text-sm"
                                    required
                                >
                                    <option
                                        v-for="item in phoneCountryCodes"
                                        :key="`${item.country}-${item.dialCode}`"
                                        :value="item.dialCode"
                                    >
                                        {{ item.name }} (+{{ item.dialCode }})
                                    </option>
                                </select>

                                <Input
                                    id="phone"
                                    v-model="form.phone_number"
                                    type="tel"
                                    required
                                    autocomplete="tel-national"
                                    inputmode="numeric"
                                    :placeholder="$t('auth.examplePhone')"
                                />
                            </div>
                            <InputError :message="form.errors.phone_country_code" />
                            <InputError :message="form.errors.phone_number" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="address">{{ $t('auth.address') }}</Label>
                        <Textarea
                            id="address"
                            v-model="form.address"
                            required
                            rows="3"
                            :placeholder="$t('auth.exampleAddress')"
                        />
                        <InputError :message="form.errors.address" />
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="password">{{ $t('auth.password') }}</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                autocomplete="new-password"
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation">{{ $t('auth.confirmPasswordLabel') }}</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                            />
                            <InputError :message="form.errors.password_confirmation" />
                        </div>
                    </div>

                    <div class="flex flex-col-reverse items-start gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
                        <Link
                            :href="route('login')"
                            class="text-sm text-muted-foreground underline underline-offset-4 transition hover:text-primary"
                        >
                            {{ $t('auth.alreadyRegisteredLogin') }}
                        </Link>

                        <Button :disabled="form.processing" class="w-full sm:w-auto">
                            {{ $t('auth.createSupplierAccountButton') }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </GuestLayout>
</template>
