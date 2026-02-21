<script setup>
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCartStore } from '@/stores/cart';
import { Head, Link } from '@inertiajs/vue3';
import { MessageCircle, Store } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';

const cartStore = useCartStore();
const { t } = useI18n();

const formatPrice = (value) =>
    new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XAF',
        maximumFractionDigits: 0,
    }).format(value);

const totalAmount = computed(() => cartStore.totalAmount);

const groupedByShop = computed(() => {
    const map = new Map();

    cartStore.items.forEach((item) => {
        const shopId = item.shop?.id ?? `unknown-${item.id}`;
        const key = String(shopId);
        const shopName = item.shop?.name || t('cartPage.unknownShop');
        const shopLogo = item.shop?.logo || null;
        const ownerPhone = item.shop?.owner_phone || null;

        if (!map.has(key)) {
            map.set(key, {
                key,
                shop: {
                    id: item.shop?.id ?? null,
                    name: shopName,
                    logo: shopLogo,
                    owner_phone: ownerPhone,
                },
                items: [],
                subtotal: 0,
            });
        }

        const group = map.get(key);
        group.items.push(item);
        group.subtotal += (Number(item.price) || 0) * item.quantity;
    });

    return Array.from(map.values());
});

const sanitizePhone = (value) => {
    const digits = String(value || '').replace(/[^\d]/g, '');
    if (!digits) return null;
    return digits.startsWith('0') ? `237${digits.replace(/^0+/, '')}` : digits;
};

const buildShopOrderMessage = (group) => {
    const lines = [
        t('cartPage.waGreeting', { shop: group.shop.name }),
        t('cartPage.waIntro'),
        '',
    ];

    group.items.forEach((item, index) => {
        lines.push(
            `${index + 1}. ${item.name}`,
            `   - ${t('cartPage.waQuantity')}: ${item.quantity}`,
            `   - ${t('cartPage.waUnitPrice')}: ${formatPrice(item.price)}`,
            `   - ${t('cartPage.waSubtotal')}: ${formatPrice((Number(item.price) || 0) * item.quantity)}`,
            `   - ${t('cartPage.waProductLink')}: ${window.location.origin}${route('products.show', { product: item.id })}`,
            '',
        );
    });

    lines.push(`${t('cartPage.waShopSubtotal')}: ${formatPrice(group.subtotal)}`);
    return encodeURIComponent(lines.join('\n'));
};

const shopWhatsappUrl = (group) => {
    const phone = sanitizePhone(group.shop.owner_phone);
    if (!phone) return null;
    return `https://wa.me/${phone}?text=${buildShopOrderMessage(group)}`;
};

const orderAllViaWhatsapp = () => {
    const urls = groupedByShop.value
        .map((group) => shopWhatsappUrl(group))
        .filter(Boolean);

    urls.forEach((url, index) => {
        setTimeout(() => {
            window.open(url, '_blank', 'noopener,noreferrer');
        }, index * 700);
    });
};

const hydrateLegacyCartItems = async () => {
    const missingIds = cartStore.items
        .filter((item) => !item.shop?.id || !item.shop?.owner_phone)
        .map((item) => item.id);

    if (!missingIds.length) return;

    try {
        const { data } = await axios.get(route('cart.metadata'), {
            params: { ids: missingIds },
        });

        const metadata = data?.metadata || {};

        cartStore.items = cartStore.items.map((item) => {
            const meta = metadata[item.id];
            if (!meta?.shop) return item;

            return {
                ...item,
                shop: {
                    id: meta.shop.id ?? item.shop?.id ?? null,
                    name: meta.shop.name ?? item.shop?.name ?? 'Boutique inconnue',
                    logo: meta.shop.logo ?? item.shop?.logo ?? null,
                    owner_phone: meta.shop.owner_phone ?? item.shop?.owner_phone ?? null,
                },
            };
        });

        cartStore.persist();
    } catch {
        // Keep existing cart state if metadata hydration fails.
    }
};

onMounted(() => {
    hydrateLegacyCartItems();
});
</script>

<template>
    <Head :title="t('cartPage.headTitle')" />

    <div>
        <ProductsNavbar />
        <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">{{ t('cartPage.title') }}</h1>
                <Link :href="route('products.index')" class="text-sm text-muted-foreground underline-offset-4 hover:underline">
                    {{ t('cartPage.continueShopping') }}
                </Link>
            </div>

            <div v-if="!cartStore.items.length" class="rounded-xl border border-dashed border-border p-10 text-center">
                <p class="text-muted-foreground">{{ t('cartPage.empty') }}</p>
                <Link :href="route('products.index')">
                    <Button class="mt-4">{{ t('cartPage.viewProducts') }}</Button>
                </Link>
            </div>

            <div v-else class="space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row">
                    <Button class="sm:w-auto" @click="orderAllViaWhatsapp">
                        <MessageCircle class="mr-2 h-4 w-4" />
                        {{ t('cartPage.orderAll') }}
                    </Button>
                    <Button variant="outline" class="sm:w-auto" @click="cartStore.clear()">
                        {{ t('cartPage.clearCart') }}
                    </Button>
                </div>

                <Card v-for="group in groupedByShop" :key="group.key">
                    <CardHeader class="space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border border-border bg-muted">
                                <img
                                    v-if="group.shop.logo"
                                    :src="group.shop.logo"
                                    :alt="`Logo ${group.shop.name}`"
                                    class="h-full w-full object-cover"
                                />
                                <Store v-else class="h-5 w-5 text-muted-foreground" />
                            </div>
                            <CardTitle class="text-lg">{{ group.shop.name }}</CardTitle>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge variant="secondary">{{ t('shopsPage.productsCount', { count: group.items.length }) }}</Badge>
                            <span class="text-sm text-muted-foreground">{{ t('cartPage.subtotal') }}: {{ formatPrice(group.subtotal) }}</span>
                        </div>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <Card v-for="item in group.items" :key="item.id">
                            <CardContent class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <img :src="item.image || '/images/Maketu1.png'" alt="Produit" class="h-14 w-14 rounded-md object-cover" />
                                    <div>
                                        <p class="font-medium">{{ item.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ formatPrice(item.price) }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        @click="cartStore.updateQuantity(item.id, item.quantity - 1)"
                                    >
                                        -
                                    </Button>
                                    <span class="min-w-8 text-center text-sm">{{ item.quantity }}</span>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        @click="cartStore.updateQuantity(item.id, item.quantity + 1)"
                                    >
                                        +
                                    </Button>
                                    <Button type="button" variant="ghost" class="text-destructive" @click="cartStore.removeItem(item.id)">
                                        {{ t('cartPage.remove') }}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <a
                            v-if="shopWhatsappUrl(group)"
                            :href="shopWhatsappUrl(group)"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="block"
                        >
                            <Button class="w-full">
                                <MessageCircle class="mr-2 h-4 w-4" />
                                {{ t('cartPage.whatsappOrder', { shop: group.shop.name }) }}
                            </Button>
                        </a>
                        <Button v-else variant="outline" disabled class="w-full">
                            {{ t('cartPage.whatsappUnavailable', { shop: group.shop.name }) }}
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('cartPage.summary') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">{{ t('cartPage.total') }}</span>
                            <span class="text-lg font-bold text-primary">{{ formatPrice(totalAmount) }}</span>
                        </div>
                        <Button class="w-full" @click="orderAllViaWhatsapp">
                            <MessageCircle class="mr-2 h-4 w-4" />
                            {{ t('cartPage.orderAll') }}
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>

