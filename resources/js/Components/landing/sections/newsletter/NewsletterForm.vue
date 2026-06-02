<script setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

/**
 * @component NewsletterForm
 * @description Form newsletter avec états de chargement et feedback.
 * @example <NewsletterForm />
 */
const email = ref('');
const loading = ref(false);
const status = ref('');
const error = ref('');
const { t } = useI18n();

const submit = async () => {
    status.value = '';
    error.value = '';

    if (!email.value.includes('@')) {
        error.value = t('landing.newsletterInvalid');
        return;
    }

    loading.value = true;
    await new Promise((resolve) => setTimeout(resolve, 900));
    loading.value = false;
    status.value = t('landing.newsletterSuccess');
    email.value = '';
};
</script>

<template>
    <form class="space-y-3" @submit.prevent="submit">
        <Label for="newsletter-email" class="sr-only">Email</Label>
        <div class="flex flex-col gap-3 sm:flex-row">
            <Input
                id="newsletter-email"
                v-model="email"
                type="email"
                autocomplete="email"
                :placeholder="t('landing.newsletterPlaceholder')"
                aria-label="Adresse email newsletter"
                required
            />
            <Button type="submit" class="px-5" :disabled="loading">
                {{ loading ? t('landing.newsletterLoading') : t('landing.newsletterSubscribe') }}
            </Button>
        </div>
        <p v-if="status" class="text-sm text-emerald-600">{{ status }}</p>
        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </form>
</template>


