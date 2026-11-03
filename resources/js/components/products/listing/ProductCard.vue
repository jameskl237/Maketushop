<script setup>
import FavoriteButton from '@/components/products/shared/FavoriteButton.vue';
import StarRating from '@/components/StarRating.vue';
import { useCart } from '@/composables/useCart';
import { Link, usePage } from '@inertiajs/vue3';
import { ShoppingCart, Store } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    product: { type: Object, required: true },
});

const { addToCart } = useCart();
const { t } = useI18n();
const page = usePage();

const amount = computed(() => Number(props.product.current_price ?? props.product.price ?? 0));
const formattedAmount = computed(() => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(amount.value));
const currencyLabel = computed(() => ((page.props.currency || 'XAF') === 'XOF' ? 'F CFA' : 'FCFA'));
const inStock = computed(() => Number(props.product.stock) > 0);
const productUrl = computed(() => route('products.show', { product: props.product.id }));
</script>

<template>
    <article class="group flex h-full flex-col overflow-hidden rounded-xl border border-border bg-card transition-shadow duration-200 hover:shadow-lg hover:shadow-black/5 dark:hover:shadow-black/40">
        <Link :href="productUrl" class="relative block aspect-square overflow-hidden bg-muted">
            <img
                :src="product.main_image || '/images/Maketu1.png'"
                :alt="product.name"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                loading="lazy"
                :style="`view-transition-name: product-img-${product.id}`"
            />
            <span
                v-if="product.is_new"
                class="absolute left-2 top-2 rounded-md bg-primary px-2 py-0.5 text-[12px] font-bold text-white"
            >{{ t('productCard.new') }}</span>
        </Link>

        <div class="flex flex-1 flex-col gap-1.5 p-3">
            <div class="flex items-start justify-between gap-2">
                <p class="line-clamp-1 text-xs font-medium text-shop-light">
                    {{ product.category?.name || t('productsPage.noCategory') }}
                </p>
                <FavoriteButton :product-id="product.id" class="-mr-1 -mt-1 h-7 w-7 shrink-0" />
            </div>

            <Link
                :href="productUrl"
                class="line-clamp-2 min-h-[40px] text-sm font-semibold leading-5 text-foreground hover:text-primary"
            >
                {{ product.name }}
            </Link>

            <StarRating
                :average-rating="Number(product.average_rating) || 0"
                :ratings-count="product.ratings_count || 0"
                readonly
                size="sm"
            />

            <p class="mt-0.5 flex items-start gap-1 font-display text-foreground">
                <span class="text-xl font-extrabold leading-none tracking-tight">{{ formattedAmount }}</span>
                <span class="text-xs font-bold leading-none">{{ currencyLabel }}</span>
            </p>

            <p class="text-xs" :class="inStock ? 'text-shop-green' : 'text-destructive'">
                {{ inStock ? t('productCard.inStock') : t('productCard.outOfStock') }}
                <span v-if="inStock" class="text-shop-light">· {{ product.delivery_time || '24-48h' }}</span>
            </p>

            <p v-if="product.shop?.name" class="flex items-center gap-1 text-xs text-shop-muted">
                <Store class="h-3.5 w-3.5 shrink-0" />
                <span class="line-clamp-1">{{ product.shop.name }}</span>
            </p>

            <div class="mt-auto grid grid-cols-2 gap-2 pt-2">
                <button
                    type="button"
                    class="flex h-9 items-center justify-center gap-1.5 rounded-full border border-border bg-muted text-xs font-bold text-foreground transition hover:bg-muted/70 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="!inStock"
                    @click="addToCart(product, 1)"
                >
                    <ShoppingCart class="h-4 w-4" />
                    {{ t('productCard.cart') }}
                </button>
                <Link
                    :href="route('products.buy', { product: product.id })"
                    class="flex h-9 items-center justify-center rounded-full bg-primary text-xs font-bold text-white transition hover:bg-primary-hover"
                    :class="!inStock ? 'pointer-events-none opacity-50' : ''"
                >
                    {{ t('productCard.buy') }}
                </Link>
            </div>
        </div>
    </article>
</template>
