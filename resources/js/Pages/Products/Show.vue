<script setup>
import ProductGallery from '@/components/products/detail/ProductGallery.vue';
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import QuantitySelector from '@/components/products/detail/QuantitySelector.vue';
import ProductCard from '@/components/products/listing/ProductCard.vue';
import FavoriteButton from '@/components/products/shared/FavoriteButton.vue';
import PriceDisplay from '@/components/products/shared/PriceDisplay.vue';
import RatingStars from '@/components/products/shared/RatingStars.vue';
import StockBadge from '@/components/products/shared/StockBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCart } from '@/composables/useCart';
import { Head, Link } from '@inertiajs/vue3';
import { ShieldCheck, Store, Truck } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    product: { type: Object, required: true },
    relatedProducts: { type: Array, default: () => [] },
});

const activeTab = ref('description');
const quantity = ref(1);
const { addToCart } = useCart();

const canBuy = computed(() => props.product.stock > 0);

const trustBadges = [
    { icon: ShieldCheck, label: 'Paiement securise' },
    { icon: Truck, label: 'Retours simplifies' },
    { icon: Store, label: 'Support vendeur' },
];

const addProductToCart = () => {
    if (!canBuy.value) return;
    addToCart(props.product, quantity.value);
};
</script>

<template>
    <Head :title="product.name" />

    <div>
        <ProductsNavbar />
        <div class="mx-auto max-w-7xl space-y-8 px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <Link href="/" class="hover:text-foreground">Accueil</Link>
                <span>/</span>
                <Link :href="route('products.index')" class="hover:text-foreground">Produits</Link>
                <span>/</span>
                <span class="text-foreground">{{ product.name }}</span>
            </div>

            <div class="grid gap-8 lg:grid-cols-2">
                <ProductGallery :images="product.images || []" :fallback-image="product.main_image || '/images/Maketu1.png'" />

                <div class="space-y-5">
                <div class="flex items-center justify-between gap-3">
                    <Badge variant="secondary">{{ product.category?.name || 'Sans categorie' }}</Badge>
                    <FavoriteButton :product-id="product.id" />
                </div>

                <div class="space-y-2">
                    <h1 class="text-2xl font-bold lg:text-3xl">{{ product.name }}</h1>
                    <p class="text-sm text-muted-foreground">{{ product.subtitle }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <RatingStars :rating="product.average_rating" :reviews-count="product.reviews_count" />
                    <span class="text-sm text-muted-foreground">{{ product.sold_count }} vendus</span>
                </div>

                <PriceDisplay
                    :current-price="product.current_price"
                    :original-price="product.original_price"
                    :discount-percentage="product.discount_percentage"
                    large
                />

                <StockBadge :stock="product.stock" />

                <Card>
                    <CardContent class="space-y-4 p-4">
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-muted-foreground">Quantite</span>
                            <QuantitySelector v-model="quantity" :max="product.stock || 1" />
                        </div>

                        <div class="grid gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="lg"
                                class="w-full"
                                :disabled="!canBuy"
                                @click="addProductToCart"
                            >
                                Ajouter au panier
                            </Button>
                            <Link :href="route('products.buy', { product: product.id })">
                                <Button type="button" size="lg" class="w-full" :disabled="!canBuy">
                                    Acheter maintenant
                                </Button>
                            </Link>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-3">
                            <div
                                v-for="item in trustBadges"
                                :key="item.label"
                                class="flex items-center gap-2 rounded-md border border-border p-2 text-xs text-muted-foreground"
                            >
                                <component :is="item.icon" class="h-4 w-4 text-primary" />
                                <span>{{ item.label }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="product.shop">
                    <CardHeader>
                        <CardTitle class="text-base">Infos vendeur</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm text-muted-foreground">
                        <p class="font-semibold text-foreground">{{ product.shop.name }}</p>
                        <p>{{ product.shop.city }}</p>
                        <p>Note vendeur: {{ product.shop.rating }}</p>
                    </CardContent>
                </Card>
                </div>
            </div>

            <div class="space-y-4">
            <div class="flex flex-wrap gap-2 border-b border-border pb-2">
                <Button type="button" :variant="activeTab === 'description' ? 'default' : 'ghost'" @click="activeTab = 'description'">
                    Description
                </Button>
                <Button type="button" :variant="activeTab === 'specs' ? 'default' : 'ghost'" @click="activeTab = 'specs'">
                    Caracteristiques
                </Button>
                <Button type="button" :variant="activeTab === 'reviews' ? 'default' : 'ghost'" @click="activeTab = 'reviews'">
                    Avis
                </Button>
            </div>

            <Card v-if="activeTab === 'description'">
                <CardContent class="prose max-w-none p-4 text-sm text-muted-foreground">
                    <p>{{ product.description || product.short_description || 'Aucune description disponible.' }}</p>
                </CardContent>
            </Card>

            <Card v-else-if="activeTab === 'specs'">
                <CardContent class="p-4">
                    <div class="divide-y divide-border">
                        <div
                            v-for="(value, key) in product.specifications || {}"
                            :key="key"
                            class="flex items-center justify-between py-2 text-sm"
                        >
                            <span class="text-muted-foreground">{{ key }}</span>
                            <span class="font-medium text-foreground">{{ value }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card v-else>
                <CardContent class="p-4 text-sm text-muted-foreground">
                    Les avis seront disponibles prochainement.
                </CardContent>
            </Card>
            </div>

            <section class="space-y-4">
                <h2 class="text-xl font-semibold">Produits similaires</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <ProductCard v-for="related in relatedProducts" :key="related.id" :product="related" />
                </div>
            </section>
        </div>
    </div>
</template>

