<script setup>
import QuantitySelector from '@/components/products/detail/QuantitySelector.vue';
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import FavoriteButton from '@/components/products/shared/FavoriteButton.vue';
import ProductCard from '@/components/products/listing/ProductCard.vue';
import ReviewsSection from '@/components/reviews/ReviewsSection.vue';
import BottomNav from '@/components/shop/BottomNav.vue';
import StarRating from '@/components/StarRating.vue';
import { Separator } from '@/components/ui/separator';
import { useCart } from '@/composables/useCart';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ChevronLeft, ChevronRight, ShieldCheck, ShoppingBag, Store, Truck } from 'lucide-vue-next';
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

    <div class="min-h-screen bg-background text-foreground">
        <ProductsNavbar />

        <main class="mx-auto max-w-[1500px] lg:px-6 lg:py-5">
            <!-- Fil d'Ariane (desktop) -->
            <nav class="mb-4 hidden items-center gap-1.5 text-sm text-shop-muted lg:flex">
                <Link href="/" class="hover:text-primary hover:underline">Accueil</Link>
                <ChevronRight class="h-3.5 w-3.5" />
                <Link :href="route('products.index')" class="hover:text-primary hover:underline">Produits</Link>
                <template v-if="product.category">
                    <ChevronRight class="h-3.5 w-3.5" />
                    <Link :href="route('products.index', { categories: [product.category.id] })" class="hover:text-primary hover:underline">{{ product.category.name }}</Link>
                </template>
                <ChevronRight class="h-3.5 w-3.5" />
                <span class="truncate text-foreground">{{ product.name }}</span>
            </nav>

            <div class="lg:grid lg:grid-cols-12 lg:gap-8 lg:rounded-xl lg:border lg:border-border lg:bg-card lg:p-6">
                <!-- ── Galerie ── -->
                <div class="lg:col-span-5">
                    <div class="lg:sticky lg:top-[124px] lg:flex lg:gap-3">
                        <!-- Vignettes verticales (desktop) -->
                        <div v-if="productImages.length > 1" class="hidden w-16 shrink-0 flex-col gap-2 lg:flex">
                            <button
                                v-for="(image, index) in productImages"
                                :key="image.id"
                                type="button"
                                class="aspect-square overflow-hidden rounded-lg border-2 bg-muted"
                                :class="activeImageIndex === index ? 'border-primary' : 'border-border hover:border-primary/50'"
                                @mouseenter="activeImageIndex = index"
                                @click="activeImageIndex = index"
                            >
                                <img :src="image.url" :alt="image.alt || product.name" class="h-full w-full object-cover" />
                            </button>
                        </div>

                        <section
                            class="relative aspect-square flex-1 overflow-hidden bg-muted sm:aspect-[4/3] lg:aspect-square lg:rounded-xl"
                            @touchstart="onImageTouchStart"
                            @touchend="onImageTouchEnd"
                        >
                            <img
                                :src="currentImage"
                                :alt="product.name"
                                class="h-full w-full object-cover"
                                :style="`view-transition-name: product-img-${product.id}`"
                            />

                            <template v-if="productImages.length > 1">
                                <button
                                    type="button"
                                    class="absolute left-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-card/90 text-foreground shadow"
                                    aria-label="Image précédente"
                                    @click="prevImage"
                                >
                                    <ChevronLeft class="h-5 w-5" />
                                </button>
                                <button
                                    type="button"
                                    class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-card/90 text-foreground shadow"
                                    aria-label="Image suivante"
                                    @click="nextImage"
                                >
                                    <ChevronRight class="h-5 w-5" />
                                </button>
                                <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5 lg:hidden">
                                    <span
                                        v-for="(image, index) in productImages"
                                        :key="image.id"
                                        class="h-1.5 rounded-full transition-all"
                                        :class="activeImageIndex === index ? 'w-4 bg-card' : 'w-1.5 bg-white/60'"
                                    />
                                </div>
                            </template>

                            <Link :href="route('products.index')" class="absolute left-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-card/90 text-foreground shadow lg:hidden" aria-label="Retour">
                                <ArrowLeft class="h-4 w-4" />
                            </Link>
                            <div class="absolute right-3 top-3 rounded-full bg-card/90 shadow">
                                <FavoriteButton :product-id="product.id" />
                            </div>
                        </section>
                    </div>

                    <!-- Vignettes horizontales (mobile) -->
                    <div v-if="productImages.length > 1" class="flex gap-2 overflow-x-auto bg-card px-4 py-2.5 lg:hidden">
                        <button
                            v-for="(image, index) in productImages"
                            :key="image.id"
                            type="button"
                            class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border-2"
                            :class="activeImageIndex === index ? 'border-primary' : 'border-transparent'"
                            @click="activeImageIndex = index"
                        >
                            <img :src="image.url" :alt="image.alt || product.name" class="h-full w-full object-cover" />
                        </button>
                    </div>
                </div>

                <!-- ── Informations ── -->
                <section class="space-y-3 bg-card px-4 py-4 lg:col-span-4 lg:bg-transparent lg:p-0">
                    <Link
                        v-if="product.category"
                        :href="route('products.index', { categories: [product.category.id] })"
                        class="text-sm font-semibold text-primary hover:underline"
                    >{{ product.category.name }}</Link>
                    <h1 class="font-display text-2xl font-extrabold leading-tight text-foreground lg:text-[28px]">{{ product.name }}</h1>
                    <p v-if="product.subtitle" class="text-[15px] leading-6 text-shop-muted">{{ product.subtitle }}</p>

                    <Link v-if="product.shop" :href="route('shops.show', { shop: product.shop.id })" class="flex w-fit items-center gap-1.5 text-sm text-primary hover:underline">
                        <Store class="h-4 w-4" /> Visiter la boutique {{ product.shop.name }}
                    </Link>

                    <StarRating
                        :rateable-id="product.id"
                        rateable-type="product"
                        :average-rating="Number(product.average_rating) || 0"
                        :ratings-count="product.ratings_count || 0"
                        :user-rating="product.user_rating || null"
                        size="md"
                    />

                    <Separator />

                    <p class="font-display text-3xl font-extrabold text-foreground">{{ price }}</p>

                    <!-- Achat (mobile) -->
                    <div class="space-y-3 lg:hidden">
                        <p class="text-sm font-semibold" :class="canBuy ? 'text-shop-green' : 'text-destructive'">
                            {{ canBuy ? 'En stock' : 'Rupture de stock' }}
                        </p>
                        <div>
                            <p class="mb-1.5 text-sm font-medium text-shop-muted">{{ t('productShow.quantity') }}</p>
                            <QuantitySelector v-model="quantity" :max="product.stock || 1" />
                        </div>
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <button
                                type="button"
                                class="flex h-11 items-center justify-center gap-2 rounded-full border-2 border-primary text-sm font-bold text-primary transition hover:bg-primary/10 disabled:opacity-50"
                                :disabled="!canBuy"
                                @click="addProductToCart"
                            >
                                <ShoppingBag class="h-4 w-4" /> Panier
                            </button>
                            <Link
                                :href="route('products.buy', { product: product.id })"
                                class="flex h-11 items-center justify-center rounded-full bg-primary text-sm font-bold text-white transition hover:bg-primary-hover"
                                :class="!canBuy ? 'pointer-events-none opacity-50' : ''"
                            >Acheter</Link>
                        </div>
                    </div>

                    <div class="pt-2">
                        <h2 class="mb-2 font-display text-lg font-bold">Description</h2>
                        <p class="whitespace-pre-line text-[15px] leading-7 text-shop-muted">
                            {{ product.description || product.short_description || t('productShow.descriptionFallback') }}
                        </p>
                    </div>
                </section>

                <!-- ── Encadré d'achat (desktop) ── -->
                <aside class="hidden lg:col-span-3 lg:block">
                    <div class="sticky top-[124px] space-y-4 rounded-xl border border-border bg-background p-5">
                        <p class="font-display text-3xl font-extrabold">{{ price }}</p>
                        <p class="flex items-center gap-2 text-sm text-shop-muted">
                            <Truck class="h-4 w-4 shrink-0" /> Livraison {{ product.delivery_time || '24-48h' }}<template v-if="product.shop?.city"> depuis {{ product.shop.city }}</template>
                        </p>
                        <p class="text-lg font-bold" :class="canBuy ? 'text-shop-green' : 'text-destructive'">
                            {{ canBuy ? 'En stock' : 'Rupture de stock' }}
                        </p>
                        <div v-if="canBuy">
                            <p class="mb-1.5 text-sm font-medium text-shop-muted">{{ t('productShow.quantity') }}</p>
                            <QuantitySelector v-model="quantity" :max="product.stock || 1" />
                        </div>
                        <div class="space-y-2.5">
                            <button
                                type="button"
                                class="flex h-11 w-full items-center justify-center gap-2 rounded-full bg-orange text-sm font-bold text-white transition hover:brightness-95 disabled:opacity-50"
                                :disabled="!canBuy"
                                @click="addProductToCart"
                            >
                                <ShoppingBag class="h-4 w-4" /> Ajouter au panier
                            </button>
                            <Link
                                :href="route('products.buy', { product: product.id })"
                                class="flex h-11 w-full items-center justify-center rounded-full bg-primary text-sm font-bold text-white transition hover:bg-primary-hover"
                                :class="!canBuy ? 'pointer-events-none opacity-50' : ''"
                            >Acheter maintenant</Link>
                        </div>
                        <div class="space-y-2 border-t border-border pt-4 text-sm">
                            <p class="flex items-center gap-2 text-shop-muted"><ShieldCheck class="h-4 w-4 shrink-0 text-shop-green" /> Paiement sécurisé Mobile Money</p>
                            <p v-if="product.shop" class="flex items-center gap-2 text-shop-muted">
                                <Store class="h-4 w-4 shrink-0" /> Vendu par
                                <Link :href="route('shops.show', { shop: product.shop.id })" class="truncate font-semibold text-primary hover:underline">{{ product.shop.name }}</Link>
                            </p>
                        </div>
                    </div>
                </aside>
            </div>

            <!-- Vendeur (mobile) -->
            <section v-if="product.shop" class="mt-2 bg-card px-4 py-4 lg:hidden">
                <h2 class="font-display text-lg font-bold">{{ t('productShow.sellerInfo') }}</h2>
                <Separator class="my-3" />
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[15px] font-bold">{{ product.shop.name }}</p>
                        <p class="text-sm text-shop-muted">{{ product.shop.city }}</p>
                    </div>
                    <Link :href="route('shops.show', { shop: product.shop.id })" class="rounded-full border border-border px-4 py-2 text-sm font-bold text-primary hover:bg-muted">
                        Voir
                    </Link>
                </div>
            </section>

            <div class="lg:mt-6 lg:overflow-hidden lg:rounded-xl lg:border lg:border-border [&>section]:lg:mt-0 [&>section]:lg:p-6">
                <ReviewsSection
                    rateable-type="product"
                    :rateable-id="product.id"
                    :reviews="product.reviews || []"
                    :average-rating="Number(product.average_rating) || 0"
                    :ratings-count="product.ratings_count || 0"
                    :user-rating="product.user_rating || null"
                />
            </div>

            <section v-if="relatedProducts.length" class="space-y-3 px-3 py-5 lg:px-0 lg:py-8">
                <h2 class="font-display text-xl font-extrabold">{{ t('productShow.relatedProducts') }}</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                    <ProductCard v-for="related in relatedProducts" :key="related.id" :product="related" />
                </div>
            </section>
        </main>

        <BottomNav />
    </div>
</template>
