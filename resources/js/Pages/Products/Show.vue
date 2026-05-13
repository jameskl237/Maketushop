<script setup>
import QuantitySelector from '@/components/products/detail/QuantitySelector.vue';
import ProductCard from '@/components/products/listing/ProductCard.vue';
import BottomNav from '@/components/shop/BottomNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCart } from '@/composables/useCart';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Heart, ShoppingBag, Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    product: { type: Object, required: true },
    relatedProducts: { type: Array, default: () => [] },
});

const quantity = ref(1);
const { addToCart } = useCart();
const { t } = useI18n();

const canBuy = computed(() => props.product.stock > 0);
const price = computed(() => {
    const value = props.product.current_price ?? props.product.price ?? 0;
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XAF',
        maximumFractionDigits: 0,
    }).format(value);
});

const addProductToCart = () => {
    if (!canBuy.value) return;
    addToCart(props.product, quantity.value);
};
</script>

<template>
    <Head :title="product.name" />

    <div class="min-h-screen bg-shop-bg pb-16 text-foreground">
        <Transition name="page-fade" mode="out-in">
            <main class="mx-auto max-w-3xl">
                <section class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-[#F0EEFF] to-[#EEF4FF]">
                    <img
                        :src="product.main_image || product.images?.[0] || '/images/Maketu1.png'"
                        :alt="product.name"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                    <Link :href="route('products.index')" class="absolute left-3 top-3">
                        <Button variant="ghost" size="icon" class="h-9 w-9 rounded-[12px] bg-white/90 shadow-none">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <Button variant="ghost" size="icon" class="absolute right-3 top-3 h-8 w-8 rounded-[12px] bg-white/90 text-pink shadow-none">
                        <Heart class="h-4 w-4" />
                    </Button>
                </section>

                <section class="space-y-3 bg-white px-4 py-3.5">
                    <p class="text-[10px] leading-none text-shop-light">
                        Accueil / Produits / {{ product.name }}
                    </p>
                    <p class="text-[10.5px] font-semibold uppercase leading-none text-primary">
                        {{ product.category?.name || t('productsPage.noCategory') }}
                    </p>
                    <h1 class="font-display text-xl font-extrabold leading-6 text-foreground">{{ product.name }}</h1>
                    <p v-if="product.subtitle" class="text-[12px] leading-5 text-shop-muted">{{ product.subtitle }}</p>

                    <div class="flex items-center justify-between gap-3">
                        <p class="font-display text-[22px] font-extrabold text-primary">{{ price }}</p>
                        <Badge class="rounded-full px-2.5 py-1 text-[10px] font-bold" :class="canBuy ? 'bg-shop-green text-white' : 'bg-pink text-white'">
                            {{ canBuy ? 'En stock' : 'Rupture' }}
                        </Badge>
                    </div>

                    <div class="flex items-center gap-1 text-[10px] text-shop-amber">
                        <Star class="h-3.5 w-3.5 fill-current" />
                        <span>{{ product.average_rating || '4.8' }}</span>
                        <span class="text-shop-light">({{ product.reviews_count || 0 }} avis)</span>
                    </div>

                    <div class="[&_>div]:rounded-[12px] [&_>div]:border-border [&_>div]:bg-shop-bg [&_button]:h-9 [&_input]:h-9">
                        <p class="mb-1.5 text-[11px] font-medium text-shop-muted">{{ t('productShow.quantity') }}</p>
                        <QuantitySelector v-model="quantity" :max="product.stock || 1" />
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <Button
                            type="button"
                            variant="outline"
                            class="h-10 rounded-[14px] border-primary bg-white text-[12px] font-bold text-primary shadow-none"
                            :disabled="!canBuy"
                            @click="addProductToCart"
                        >
                            <ShoppingBag class="h-4 w-4" />
                            Panier
                        </Button>
                        <Link :href="route('products.buy', { product: product.id })">
                            <Button class="h-10 w-full rounded-[14px] bg-gradient-to-r from-primary to-pink text-[12px] font-bold text-white shadow-none" :disabled="!canBuy">
                                Acheter
                            </Button>
                        </Link>
                    </div>
                </section>

                <section class="mt-2 bg-white px-4 py-3.5">
                    <h2 class="font-display text-[16px] font-bold">Description</h2>
                    <p class="mt-2 text-[12px] leading-5 text-shop-muted">
                        {{ product.description || product.short_description || t('productShow.descriptionFallback') }}
                    </p>
                </section>

                <section v-if="product.shop" class="mt-2 bg-white px-4 py-3.5">
                    <h2 class="font-display text-[16px] font-bold">{{ t('productShow.sellerInfo') }}</h2>
                    <Separator class="my-3" />
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[13px] font-bold">{{ product.shop.name }}</p>
                            <p class="text-[11px] text-shop-muted">{{ product.shop.city }}</p>
                        </div>
                        <Link :href="route('shops.show', { shop: product.shop.id })">
                            <Button variant="outline" class="h-[30px] rounded-[10px] border-border bg-shop-bg text-[11px] font-bold text-primary shadow-none">
                                Voir
                            </Button>
                        </Link>
                    </div>
                </section>

                <section v-if="relatedProducts.length" class="space-y-3 px-3 py-4">
                    <h2 class="font-display text-[17px] font-extrabold">{{ t('productShow.relatedProducts') }}</h2>
                    <div class="grid grid-cols-2 gap-2.5">
                        <ProductCard v-for="related in relatedProducts" :key="related.id" :product="related" />
                    </div>
                </section>
            </main>
        </Transition>

        <BottomNav />
    </div>
</template>
