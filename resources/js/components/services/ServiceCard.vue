<script setup>
import FavoriteButton from '@/components/products/shared/FavoriteButton.vue';
import StarRating from '@/components/StarRating.vue';
import { useCart } from '@/composables/useCart';
import { Link } from '@inertiajs/vue3';
import { FileText, Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    service: { type: Object, required: true },
});

const { addToCart } = useCart();
const { t } = useI18n();

const isQuoteOnly = computed(() => props.service.quote_only || props.service.current_price == null);

const price = computed(() => {
    if (isQuoteOnly.value) return null;
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XAF',
        maximumFractionDigits: 0,
    }).format(props.service.current_price ?? 0);
});

const onAddToCart = () => {
    addToCart({ ...props.service, type: 'service' }, 1);
};
</script>

<template>
    <article class="group flex h-full flex-col overflow-hidden rounded-xl border border-border bg-card transition-shadow duration-200 hover:shadow-lg hover:shadow-black/5 dark:hover:shadow-black/40">
        <Link :href="route('services.show', { service: service.id })" class="relative block aspect-square overflow-hidden bg-muted">
            <img
                :src="service.main_image || '/images/Maketu1.png'"
                :alt="service.name"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                loading="lazy"
                :style="`view-transition-name: service-img-${service.id}`"
            />
            <div class="absolute left-2 top-2 flex flex-col items-start gap-1">
                <span v-if="service.is_new" class="rounded-md bg-primary px-2 py-0.5 text-[11px] font-bold text-white">{{ t('servicesPage.newBadge') }}</span>
                <span class="flex items-center rounded-md bg-orange px-2 py-0.5 text-[11px] font-bold text-white">
                    <Sparkles class="mr-1 h-3 w-3" /> {{ t('servicesPage.serviceLabel') }}
                </span>
            </div>
        </Link>

        <div class="flex flex-1 flex-col gap-1.5 p-3">
            <div class="flex items-start justify-between gap-2">
                <p class="line-clamp-1 text-xs font-medium text-shop-light">
                    {{ service.category?.name || t('servicesPage.serviceLabel') }}
                </p>
                <FavoriteButton :item-id="service.id" type="service" class="-mr-1 -mt-1 h-7 w-7 shrink-0" />
            </div>

            <Link
                :href="route('services.show', { service: service.id })"
                class="line-clamp-2 min-h-[40px] text-sm font-semibold leading-5 text-foreground hover:text-primary"
            >
                {{ service.name }}
            </Link>

            <StarRating
                :average-rating="Number(service.average_rating) || 0"
                :ratings-count="service.ratings_count || 0"
                readonly
                size="sm"
            />

            <p v-if="price" class="font-display text-xl font-extrabold leading-none text-foreground">{{ price }}</p>
            <p v-else class="text-sm font-bold text-primary">{{ t('servicesPage.quoteOnlyPrice') }}</p>

            <p class="line-clamp-1 text-xs text-shop-muted">
                {{ service.shop?.name }}<template v-if="service.city"> · {{ service.city }}</template>
            </p>

            <div class="mt-auto pt-2">
                <Link
                    v-if="isQuoteOnly"
                    :href="route('services.show', { service: service.id })"
                    class="flex h-9 w-full items-center justify-center gap-1.5 rounded-full bg-primary text-xs font-bold text-white transition hover:bg-primary-hover"
                >
                    <FileText class="h-4 w-4" /> {{ t('servicesPage.requestQuote') }}
                </Link>
                <div v-else class="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        class="flex h-9 items-center justify-center rounded-full border border-border bg-muted text-xs font-bold text-foreground transition hover:bg-muted/70"
                        @click="onAddToCart"
                    >
                        {{ t('servicesPage.orderButton') }}
                    </button>
                    <Link
                        :href="route('services.buy', { service: service.id })"
                        class="flex h-9 items-center justify-center rounded-full bg-primary text-xs font-bold text-white transition hover:bg-primary-hover"
                    >
                        {{ t('servicesPage.buyButton') }}
                    </Link>
                </div>
            </div>
        </div>
    </article>
</template>
