<script setup>
import QuantitySelector from '@/components/products/detail/QuantitySelector.vue';
import ProductCard from '@/components/products/listing/ProductCard.vue';
import ReviewsSection from '@/components/reviews/ReviewsSection.vue';
import BottomNav from '@/components/shop/BottomNav.vue';
import StarRating from '@/components/StarRating.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCart } from '@/composables/useCart';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ChevronLeft, ChevronRight, Heart, ShoppingBag } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    product: { type: Object, required: true },
    relatedProducts: { type: Array, default: () => [] },
});

const quantity = ref(1);
const { addToCart } = useCart();
const { t } = useI18n();

const origin = typeof window !== 'undefined' ? window.location.origin : '';
const ogImage = computed(() => {
    const img = props.product.main_image || '/images/Maketu_logo.png';
    if (/^https?:\/\//.test(img)) return img;
    return `${origin}${img.startsWith('/') ? '' : '/'}${img}`;
});

const productImages = computed(() => {
    if (props.product.images?.length) return props.product.images;
    const fallbackUrl = props.product.main_image || '/images/Maketu1.png';
    return [{ id: 'main', url: fallbackUrl, alt: props.product.name }];
});
const activeImageIndex = ref(0);
const currentImage = computed(() => productImages.value[activeImageIndex.value]?.url);

const prevImage = () => {
    activeImageIndex.value = (activeImageIndex.value - 1 + productImages.value.length) % productImages.value.length;
};
const nextImage = () => {
    activeImageIndex.value = (activeImageIndex.value + 1) % productImages.value.length;
};

let touchStartX = 0;
const onImageTouchStart = (event) => {
    touchStartX = event.changedTouches[0].clientX;
};
const onImageTouchEnd = (event) => {
    if (productImages.value.length < 2) return;
    const deltaX = event.changedTouches[0].clientX - touchStartX;
    if (Math.abs(deltaX) < 40) return;
    if (deltaX < 0) nextImage();
    else prevImage();
};

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
    <Head :title="product.name">
        <meta head-key="og:title" property="og:title" :content="product.name" />
        <meta head-key="og:description" property="og:description" :content="product.short_description || product.subtitle || 'Découvrez ce produit sur MaketuShop.'" />
        <meta head-key="og:image" property="og:image" :content="ogImage" />
        <meta head-key="og:type" property="og:type" content="product" />
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
    </Head>

    <div class="min-h-screen bg-shop-bg pb-16 text-foreground">
        <Transition name="page-fade" mode="out-in">
            <main class="mx-auto max-w-3xl">
                <section
                    class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-[#F0EEFF] to-[#EEF4FF]"
                    @touchstart="onImageTouchStart"
                    @touchend="onImageTouchEnd"
                >
                    <img
                        :src="currentImage"
                        :alt="product.name"
                        class="h-full w-full object-cover"
                        loading="lazy"
                        :style="`view-transition-name: product-img-${product.id}`"
                    />

                    <template v-if="productImages.length > 1">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="absolute left-2 top-1/2 h-8 w-8 -translate-y-1/2 rounded-[12px] bg-white/90 shadow-none"
                            @click="prevImage"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="absolute right-2 top-1/2 h-8 w-8 -translate-y-1/2 rounded-[12px] bg-white/90 shadow-none"
                            @click="nextImage"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>

                        <div class="absolute bottom-2 left-1/2 flex -translate-x-1/2 gap-1.5">
                            <button
                                v-for="(image, index) in productImages"
                                :key="image.id"
                                type="button"
                                class="h-1.5 rounded-full transition-all"
                                :class="activeImageIndex === index ? 'w-4 bg-white' : 'w-1.5 bg-white/60'"
                                @click="activeImageIndex = index"
                            />
                        </div>
                    </template>

                    <Link :href="route('products.index')" class="absolute left-3 top-3">
                        <Button variant="ghost" size="icon" class="h-9 w-9 rounded-[12px] bg-white/90 shadow-none">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <Button variant="ghost" size="icon" class="absolute right-3 top-3 h-8 w-8 rounded-[12px] bg-white/90 text-pink shadow-none">
                        <Heart class="h-4 w-4" />
                    </Button>
                </section>

                <div v-if="productImages.length > 1" class="flex gap-2 overflow-x-auto bg-white px-4 py-2.5">
                    <button
                        v-for="(image, index) in productImages"
                        :key="image.id"
                        type="button"
                        class="h-14 w-14 shrink-0 overflow-hidden rounded-[10px] border-2"
                        :class="activeImageIndex === index ? 'border-primary' : 'border-transparent'"
                        @click="activeImageIndex = index"
                    >
                        <img :src="image.url" :alt="image.alt || product.name" class="h-full w-full object-cover" />
                    </button>
                </div>

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

                    <StarRating
                        :rateable-id="product.id"
                        rateable-type="product"
                        :average-rating="Number(product.average_rating) || 0"
                        :ratings-count="product.ratings_count || 0"
                        :user-rating="product.user_rating || null"
                        size="md"
                    />

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
                            <Button class="h-10 w-full rounded-[14px] bg-primary text-[12px] font-bold text-white shadow-none" :disabled="!canBuy">
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

                <ReviewsSection
                    rateable-type="product"
                    :rateable-id="product.id"
                    :reviews="product.reviews || []"
                    :average-rating="Number(product.average_rating) || 0"
                    :ratings-count="product.ratings_count || 0"
                    :user-rating="product.user_rating || null"
                />

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
