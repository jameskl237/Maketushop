<script setup>
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import PriceDisplay from '@/components/products/shared/PriceDisplay.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    product: { type: Object, required: true },
});
const { t } = useI18n();

const sellerPhone = computed(() => props.product.shop?.owner_phone || '');

const whatsappPhone = computed(() => {
    const raw = String(sellerPhone.value || '');
    const digits = raw.replace(/[^\d]/g, '');
    if (!digits) return '';
    return digits.startsWith('0') ? `237${digits.replace(/^0+/, '')}` : digits;
});

const whatsappText = computed(() => {
    const lines = [
        t('productBuy.waHello'),
        `- ${t('productBuy.waName')}: ${props.product.name}`,
        `- ${t('productBuy.waPrice')}: ${props.product.current_price} FCFA`,
        `- ${t('productBuy.waShop')}: ${props.product.shop?.name || 'N/A'}`,
        `- ${t('productBuy.waImage')}: ${props.product.main_image || ''}`,
        `- ${t('productBuy.waLink')}: ${window.location.origin}${route('products.show', { product: props.product.id })}`,
    ];

    return encodeURIComponent(lines.join('\n'));
});

const whatsappUrl = computed(() => {
    if (!whatsappPhone.value) return '#';
    return `https://wa.me/${whatsappPhone.value}?text=${whatsappText.value}`;
});
</script>

<template>
    <Head :title="`${t('productsPage.buy')} - ${product.name}`" />

    <div>
        <ProductsNavbar />
        <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <Link :href="route('products.index')" class="hover:text-foreground">{{ t('public.products') }}</Link>
                <span>/</span>
                <Link :href="route('products.show', { product: product.id })" class="hover:text-foreground">
                    {{ product.name }}
                </Link>
                <span>/</span>
                <span class="text-foreground">{{ t('productBuy.breadcrumbBuy') }}</span>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('productBuy.title') }}</CardTitle>
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
                                {{ t('productBuy.whatsappBuy') }}
                            </Button>
                        </a>

                        <Link :href="route('payments.unavailable')">
                            <Button variant="outline" class="w-full">
                                {{ t('productBuy.platformBuy') }}
                            </Button>
                        </Link>
                    </div>

                    <p v-if="!whatsappPhone" class="text-sm text-destructive">
                        {{ t('productBuy.phoneUnavailable') }}
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

