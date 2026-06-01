<script setup>
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    service: { type: Object, required: true },
});

const publicBaseUrl = (import.meta.env.VITE_PUBLIC_APP_URL || window.location.origin).replace(/\/+$/, '');

const price = computed(() => {
    if (props.service.current_price == null) return 'Sur devis';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XAF',
        maximumFractionDigits: 0,
    }).format(props.service.current_price);
});

const whatsappPhone = computed(() => {
    const digits = String(props.service.shop?.owner_phone || '').replace(/[^\d]/g, '');
    if (!digits) return '';
    return digits.startsWith('0') ? `237${digits.replace(/^0+/, '')}` : digits;
});

const whatsappUrl = computed(() => {
    if (!whatsappPhone.value) return '#';
    const serviceUrl = `${publicBaseUrl}/services/${props.service.id}`;
    const lines = [
        `Bonjour ${props.service.shop?.name ?? ''},`.trim(),
        '',
        `Je souhaite commander le service : ${props.service.name}`,
        props.service.current_price != null ? `Prix : ${props.service.current_price} FCFA` : 'Prix : sur devis',
        serviceUrl,
    ];
    return `https://wa.me/${whatsappPhone.value}?text=${encodeURIComponent(lines.join('\n'))}`;
});
</script>

<template>
    <Head :title="`Commander - ${service.name}`" />

    <div>
        <ProductsNavbar />
        <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <Link :href="route('services.index')" class="hover:text-foreground">Services</Link>
                <span>/</span>
                <Link :href="route('services.show', { service: service.id })" class="hover:text-foreground">
                    {{ service.name }}
                </Link>
                <span>/</span>
                <span class="text-foreground">Commander</span>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Commander ce service</CardTitle>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div class="flex items-start gap-4">
                        <img :src="service.main_image || '/images/Maketu1.png'" alt="Service" class="h-24 w-24 rounded-md object-cover" />
                        <div class="space-y-1">
                            <p class="text-lg font-semibold">{{ service.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ service.shop?.name }}</p>
                            <p class="font-display text-lg font-extrabold text-primary">{{ price }}</p>
                        </div>
                    </div>

                    <a :href="whatsappUrl" target="_blank" rel="noopener noreferrer" class="block">
                        <Button class="w-full" :disabled="!whatsappPhone">
                            Commander via WhatsApp
                        </Button>
                    </a>

                    <p class="text-center text-xs text-muted-foreground">
                        Le paiement en ligne pour les services sera bientôt disponible.
                        Pour l'instant, finalisez votre commande directement avec le prestataire.
                    </p>

                    <p v-if="!whatsappPhone" class="text-sm text-destructive">
                        Le contact WhatsApp de ce prestataire n'est pas disponible.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
