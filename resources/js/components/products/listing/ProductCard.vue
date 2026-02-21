<script setup>
import FavoriteButton from '@/components/products/shared/FavoriteButton.vue';
import PriceDisplay from '@/components/products/shared/PriceDisplay.vue';
import RatingStars from '@/components/products/shared/RatingStars.vue';
import StockBadge from '@/components/products/shared/StockBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useCart } from '@/composables/useCart';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    product: { type: Object, required: true },
});

const { addToCart } = useCart();
const { t } = useI18n();

const onAddToCart = () => {
    addToCart(props.product, 1);
};
</script>

<template>
    <Card class="group overflow-hidden rounded-xl border transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
        <CardContent class="p-0">
            <Link :href="route('products.show', { product: product.id })" class="block">
                <div class="relative aspect-square overflow-hidden bg-muted">
                    <img
                        :src="product.main_image || '/images/Maketu1.png'"
                        :alt="product.name"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
                        loading="lazy"
                    />
                    <div class="absolute left-2 top-2 flex gap-2">
                        <Badge v-if="product.is_new" variant="secondary">{{ t('productsPage.new') }}</Badge>
                        <Badge v-if="product.discount_percentage" class="bg-destructive text-destructive-foreground">
                            -{{ product.discount_percentage }}%
                        </Badge>
                    </div>
                    <div class="absolute right-2 top-2">
                        <FavoriteButton :product-id="product.id" />
                    </div>
                    <div class="absolute bottom-2 left-2">
                        <StockBadge :stock="product.stock" />
                    </div>
                </div>
            </Link>

            <div class="space-y-3 p-4">
                <div class="space-y-1">
                    <p class="text-xs text-muted-foreground">{{ product.category?.name || t('productsPage.noCategory') }}</p>
                    <Link
                        :href="route('products.show', { product: product.id })"
                        class="line-clamp-2 text-sm font-semibold leading-5 hover:text-primary"
                    >
                        {{ product.name }}
                    </Link>
                    <p class="text-xs text-muted-foreground">{{ product.shop?.name }}</p>
                </div>

                <RatingStars :rating="product.average_rating" :reviews-count="product.reviews_count" />
                <PriceDisplay
                    :current-price="product.current_price"
                    :original-price="product.original_price"
                    :discount-percentage="product.discount_percentage"
                />
                <p class="text-xs text-muted-foreground">{{ t('productsPage.delivery') }}: {{ product.delivery_time }}</p>

                <div class="grid grid-cols-2 gap-2">
                    <Button
                        variant="outline"
                        class="w-full"
                        :disabled="product.stock <= 0"
                        @click="onAddToCart"
                    >
                        {{ t('productsPage.add') }}
                    </Button>
                    <Link :href="route('products.buy', { product: product.id })">
                        <Button class="w-full" :disabled="product.stock <= 0">
                            {{ t('productsPage.buy') }}
                        </Button>
                    </Link>
                </div>
            </div>
        </CardContent>
    </Card>
</template>

