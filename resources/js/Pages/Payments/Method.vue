<script setup>
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import PriceDisplay from '@/components/products/shared/PriceDisplay.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCartStore } from '@/stores/cart';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Check, CreditCard, Lock } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    context: {
        type: String,
        required: true,
    },
    product: {
        type: Object,
        default: null,
    },
    methods: {
        type: Array,
        default: () => [],
    },
});

const { t } = useI18n();
const cartStore = useCartStore();
const selectedMethod = ref(props.methods[0] || null);

const formatPrice = (value) =>
    new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XAF',
        maximumFractionDigits: 0,
    }).format(value);

const isCart = computed(() => props.context === 'cart');
const cartItems = computed(() => cartStore.items);
const cartTotal = computed(() => cartStore.totalAmount);
const cartItemsCount = computed(() => cartStore.itemsCount);

const canSubmit = computed(() => {
    if (!selectedMethod.value) return false;
    return !isCart.value || cartItems.value.length > 0;
});

const submitPayment = () => {
    if (!canSubmit.value) return;

    const payload = {
        payment_channel: selectedMethod.value.channel,
    };

    if (isCart.value) {
        payload.items = cartItems.value.map((item) => ({
            id: item.id,
            quantity: item.quantity,
        }));

        router.post(route('payments.cart.checkout'), payload);
        return;
    }

    router.post(route('payments.checkout', { product: props.product.id }), payload);
};
</script>

<template>
    <Head :title="t('payments.methodTitle')" />

    <div>
        <ProductsNavbar />

        <main class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <Link
                :href="isCart ? route('cart.index') : route('products.buy', { product: product.id })"
                class="inline-flex items-center gap-2 text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
            >
                <ArrowLeft class="h-4 w-4" />
                {{ t('payments.back') }}
            </Link>

            <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
                <section class="space-y-4">
                    <div>
                        <h1 class="text-2xl font-bold">{{ t('payments.methodTitle') }}</h1>
                        <p class="mt-1 text-sm text-muted-foreground">{{ t('payments.methodSubtitle') }}</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <button
                            v-for="method in methods"
                            :key="method.key"
                            type="button"
                            :class="[
                                'relative rounded-lg border bg-card p-4 text-left transition hover:border-primary/60 hover:bg-accent/40',
                                selectedMethod?.key === method.key ? 'border-primary ring-2 ring-primary/20' : 'border-border',
                            ]"
                            @click="selectedMethod = method"
                        >
                            <span
                                v-if="selectedMethod?.key === method.key"
                                class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground"
                            >
                                <Check class="h-4 w-4" />
                            </span>
                            <img :src="method.image" :alt="method.name" class="h-24 w-full rounded-md object-contain" />
                            <span class="mt-3 block font-semibold">{{ method.name }}</span>
                            <span class="mt-1 block text-sm text-muted-foreground">{{ method.description }}</span>
                        </button>
                    </div>
                </section>

                <aside>
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <CreditCard class="h-4 w-4" />
                                {{ t('payments.summary') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-if="isCart" class="space-y-3">
                                <div v-if="!cartItems.length" class="rounded-md border border-dashed border-border p-4 text-sm text-muted-foreground">
                                    {{ t('cartPage.empty') }}
                                </div>
                                <div v-else class="space-y-2">
                                    <div
                                        v-for="item in cartItems"
                                        :key="item.id"
                                        class="flex items-center justify-between gap-3 text-sm"
                                    >
                                        <span class="line-clamp-1">{{ item.name }} x{{ item.quantity }}</span>
                                        <span class="font-medium">{{ formatPrice((Number(item.price) || 0) * item.quantity) }}</span>
                                    </div>
                                </div>
                                <div class="border-t border-border pt-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-muted-foreground">{{ t('dashboard.client.items', { count: cartItemsCount }) }}</span>
                                        <span class="text-lg font-bold text-primary">{{ formatPrice(cartTotal) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <img :src="product.image || '/images/Maketu1.png'" alt="Produit" class="h-16 w-16 rounded-md object-cover" />
                                    <div>
                                        <p class="font-semibold">{{ product.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ product.shop_name }}</p>
                                        <PriceDisplay :current-price="product.price" />
                                    </div>
                                </div>
                            </div>

                            <Button class="w-full" :disabled="!canSubmit" @click="submitPayment">
                                <Lock class="mr-2 h-4 w-4" />
                                {{ t('payments.continueToNotchPay') }}
                            </Button>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </main>
    </div>
</template>
