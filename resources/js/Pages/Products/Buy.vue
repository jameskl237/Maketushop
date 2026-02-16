<script setup>
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import PriceDisplay from '@/components/products/shared/PriceDisplay.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    product: { type: Object, required: true },
});

const sellerPhone = computed(() => props.product.shop?.owner_phone || '');

const whatsappPhone = computed(() => {
    const raw = String(sellerPhone.value || '');
    const digits = raw.replace(/[^\d]/g, '');
    if (!digits) return '';
    return digits.startsWith('0') ? `237${digits.replace(/^0+/, '')}` : digits;
});

const whatsappText = computed(() => {
    const lines = [
        'Bonjour, je suis interesse par ce produit:',
        `- Nom: ${props.product.name}`,
        `- Prix: ${props.product.current_price} FCFA`,
        `- Boutique: ${props.product.shop?.name || 'N/A'}`,
        `- Lien image: ${props.product.main_image || ''}`,
        `- Lien produit: ${window.location.origin}${route('products.show', { product: props.product.id })}`,
    ];

    return encodeURIComponent(lines.join('\n'));
});

const whatsappUrl = computed(() => {
    if (!whatsappPhone.value) return '#';
    return `https://wa.me/${whatsappPhone.value}?text=${whatsappText.value}`;
});
</script>

<template>
    <Head :title="`Acheter - ${product.name}`" />

    <div>
        <ProductsNavbar />
        <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <Link :href="route('products.index')" class="hover:text-foreground">Produits</Link>
                <span>/</span>
                <Link :href="route('products.show', { product: product.id })" class="hover:text-foreground">
                    {{ product.name }}
                </Link>
                <span>/</span>
                <span class="text-foreground">Achat</span>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Choisissez votre mode d'achat</CardTitle>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div class="flex items-start gap-4">
                        <img :src="product.main_image || '/images/Maketu1.png'" alt="Produit" class="h-24 w-24 rounded-md object-cover" />
                        <div class="space-y-1">
                            <p class="text-lg font-semibold">{{ product.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ product.shop?.name }}</p>
                            <PriceDisplay
                                :current-price="product.current_price"
                                :original-price="product.original_price"
                                :discount-percentage="product.discount_percentage"
                            />
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="block"
                        >
                            <Button class="w-full" :disabled="!whatsappPhone">
                                Acheter sur WhatsApp
                            </Button>
                        </a>

                        <Link :href="route('payments.unavailable')">
                            <Button variant="outline" class="w-full">
                                Acheter sur la plateforme
                            </Button>
                        </Link>
                    </div>

                    <p v-if="!whatsappPhone" class="text-sm text-destructive">
                        Le numero WhatsApp du proprietaire de la boutique n'est pas disponible.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

