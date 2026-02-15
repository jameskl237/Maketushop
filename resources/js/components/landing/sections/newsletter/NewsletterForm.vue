<script setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ref } from 'vue';

/**
 * @component NewsletterForm
 * @description Form newsletter avec états de chargement et feedback.
 * @example <NewsletterForm />
 */
const email = ref('');
const loading = ref(false);
const status = ref('');
const error = ref('');

const submit = async () => {
    status.value = '';
    error.value = '';

    if (!email.value.includes('@')) {
        error.value = 'Veuillez entrer une adresse email valide.';
        return;
    }

    loading.value = true;
    await new Promise((resolve) => setTimeout(resolve, 900));
    loading.value = false;
    status.value = 'Merci ! Vous êtes inscrit à la newsletter.';
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
                placeholder="Votre email professionnel"
                aria-label="Adresse email newsletter"
                required
            />
            <Button type="submit" :disabled="loading">
                {{ loading ? 'Inscription...' : "S'abonner" }}
            </Button>
        </div>
        <p v-if="status" class="text-sm text-emerald-600">{{ status }}</p>
        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </form>
</template>


