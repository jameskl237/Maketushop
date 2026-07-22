<script setup>
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Home } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    status: { type: Number, required: true },
});

const content = computed(() => {
    return (
        {
            403: {
                title: 'Accès refusé',
                message: "Vous n'avez pas la permission d'accéder à cette page.",
            },
            404: {
                title: 'Page introuvable',
                message: "Ce produit ou cette page n'existe plus, ou l'adresse n'est pas correcte.",
            },
            500: {
                title: 'Oups, une erreur est survenue',
                message: 'Un problème technique est survenu de notre côté. Merci de réessayer dans quelques instants.',
            },
            503: {
                title: 'Service momentanément indisponible',
                message: 'MaketuShop est en maintenance. Merci de revenir un peu plus tard.',
            },
        }[props.status] || {
            title: 'Une erreur est survenue',
            message: 'Quelque chose ne s\'est pas passé comme prévu.',
        }
    );
});

const goBack = () => {
    if (window.history.length > 1) window.history.back();
    else window.location.assign(route('home'));
};
</script>

<template>
    <Head :title="content.title" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-[#F0EEFF] to-[#EEF4FF] px-6 py-12 text-center text-foreground">
        <img src="/images/Maketu_logo.png" alt="MaketuShop" class="h-20 w-20 rounded-2xl object-contain shadow-lg shadow-primary/10" />

        <p class="mt-6 font-display text-6xl font-extrabold text-primary">{{ status }}</p>
        <h1 class="mt-2 font-display text-xl font-extrabold text-foreground">{{ content.title }}</h1>
        <p class="mt-2 max-w-xs text-[13px] leading-5 text-shop-muted">{{ content.message }}</p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-2.5">
            <Button variant="ghost" class="h-10 rounded-[14px] text-[12px] font-bold text-primary" @click="goBack">
                <ArrowLeft class="h-4 w-4" />
                Retour
            </Button>
            <Link :href="route('home')">
                <Button class="h-10 rounded-[14px] bg-primary text-[12px] font-bold text-white shadow-none">
                    <Home class="h-4 w-4" />
                    Accueil MaketuShop
                </Button>
            </Link>
        </div>
    </div>
</template>
