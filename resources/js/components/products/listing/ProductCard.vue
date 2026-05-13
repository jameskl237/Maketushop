<script setup>
import FavoriteButton from '@/components/products/shared/FavoriteButton.vue';
import StarRating from '@/components/StarRating.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useCart } from '@/composables/useCart';
import { Link } from '@inertiajs/vue3';
import { ShoppingBag } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    product: { type: Object, required: true },
});

const { addToCart } = useCart();
const { t } = useI18n();

const price = computed(() => {
    const value = props.product.current_price ?? props.product.price ?? 0;
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XAF',
        maximumFractionDigits: 0,
    }).format(value);
});

const onAddToCart = () => {
    addToCart(props.product, 1);
};
</script>

<template>
    <Card class="group overflow-hidden rounded-[24px] border-none bg-transparent shadow-none transition-all duration-300 active:scale-[0.98]">
        <CardContent class="p-0">
            <Link :href="route('products.show', { product: product.id })" class="block">
                <div class="relative aspect-square overflow-hidden rounded-[24px] bg-gradient-to-br from-[#F0EEFF] to-[#E8F4FF] shadow-sm group-hover:shadow-xl transition-shadow duration-300">
                    <img
                        :src="product.main_image || '/images/Maketu1.png'"
                        :alt="product.name"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                        loading="lazy"
                        :style="`view-transition-name: product-img-${product.id}`"
                    />
                    <!-- Inner Glow Effect -->
                    <div class="absolute inset-0 ring-1 ring-inset ring-white/20"></div>

                    <div class="absolute left-3 top-3 flex flex-col gap-1.5">
                        <Badge v-if="product.is_new" class="h-6 rounded-lg bg-primary/90 px-2.5 text-[10px] font-bold text-white backdrop-blur-md">
                            New
                        </Badge>
                        <Badge v-if="product.stock > 0" class="h-6 rounded-lg bg-shop-green/90 px-2.5 text-[10px] font-bold text-white backdrop-blur-md">
                            Stock
                        </Badge>
                    </div>

                    <div class="absolute right-3 top-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/70 backdrop-blur-md transition-colors hover:bg-white">
                            <FavoriteButton :product-id="product.id" />
                        </div>
                    </div>

                    <!-- Glass Price Capsule -->
                    <div class="absolute bottom-3 right-3 rounded-xl border border-white/40 bg-white/70 px-3 py-1.5 backdrop-blur-md shadow-lg shadow-black/5">
                        <p class="font-display text-[13px] font-black leading-none text-primary">{{ price }}</p>
                    </div>
                </div>
            </Link>

            <div class="space-y-2 px-1.5 py-3">
                <div class="flex items-center justify-between gap-2">
                    <p class="line-clamp-1 text-[10px] font-bold uppercase tracking-wider text-shop-light">
                        {{ product.category?.name || t('productsPage.noCategory') }}
                    </p>
                    <StarRating
                        :rateable-id="product.id"
                        rateable-type="product"
                        :average-rating="product.average_rating || 0"
                        :ratings-count="product.ratings_count || 0"
                        :user-rating="product.user_rating || null"
                        size="sm"
                    />
                </div>

                <Link
                    :href="route('products.show', { product: product.id })"
                    class="line-clamp-2 min-h-[36px] font-display text-[14px] font-bold leading-snug text-foreground transition-colors group-hover:text-primary"
                >
                    {{ product.name }}
                </Link>

                <div class="flex items-center justify-between gap-2 border-t border-border pt-2">
                    <p class="line-clamp-1 text-[11px] font-medium text-shop-light">{{ product.shop?.name }}</p>
                    <p class="whitespace-nowrap text-[10px] text-shop-light/70">
                        {{ product.delivery_time || '24-48h' }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <Button
                        variant="glass"
                        class="h-9 rounded-xl px-1 text-[11px] font-bold"
                        :disabled="product.stock <= 0"
                        @click="onAddToCart"
                    >
                        <ShoppingBag class="h-3.5 w-3.5" />
                        Panier
                    </Button>
                    <Link :href="route('products.buy', { product: product.id })" class="w-full">
                        <Button
                            variant="default"
                            class="h-9 w-full rounded-xl px-1 text-[11px] font-bold"
                            :disabled="product.stock <= 0"
                        >
                            Acheter
                        </Button>
                    </Link>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
