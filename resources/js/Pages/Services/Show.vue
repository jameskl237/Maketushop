<script setup>
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import ReviewsSection from '@/components/reviews/ReviewsSection.vue';
import ServiceCard from '@/components/services/ServiceCard.vue';
import QuoteDialog from '@/components/services/QuoteDialog.vue';
import BottomNav from '@/components/shop/BottomNav.vue';
import StarRating from '@/components/StarRating.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCart } from '@/composables/useCart';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, MapPin, ShoppingBag } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    service: { type: Object, required: true },
    relatedServices: { type: Array, default: () => [] },
});

const { addToCart } = useCart();
const { t } = useI18n();

const origin = typeof window !== 'undefined' ? window.location.origin : '';
const ogImage = computed(() => {
    const img = props.service.main_image || '/images/Maketu_logo.png';
    if (/^https?:\/\//.test(img)) return img;
    return `${origin}${img.startsWith('/') ? '' : '/'}${img}`;
});

const isQuoteOnly = computed(() => props.service.quote_only || props.service.current_price == null);

const price = computed(() => {
    if (isQuoteOnly.value) return null;
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XAF',
        maximumFractionDigits: 0,
    }).format(props.service.current_price ?? 0);
});

const orderService = () => {
    addToCart({ ...props.service, type: 'service' }, 1);
};
</script>

<template>
    <Head :title="service.name">
        <meta head-key="og:title" property="og:title" :content="service.name" />
        <meta head-key="og:description" property="og:description" :content="service.short_description || service.subtitle || t('serviceShow.ogDescriptionFallback')" />
        <meta head-key="og:image" property="og:image" :content="ogImage" />
        <meta head-key="og:type" property="og:type" content="product" />
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <ProductsNavbar />
        <Transition name="page-fade" mode="out-in">
            <main class="mx-auto max-w-[1500px] lg:px-6 lg:py-5">
              <div class="lg:grid lg:grid-cols-12 lg:gap-8 lg:rounded-xl lg:border lg:border-border lg:bg-card lg:p-6">
                <section class="relative aspect-[4/3] overflow-hidden bg-muted lg:col-span-5 lg:aspect-square lg:self-start lg:rounded-xl">
                    <img
                        :src="service.main_image || service.images?.[0]?.url || '/images/Maketu1.png'"
                        :alt="service.name"
                        class="h-full w-full object-cover"
                        loading="lazy"
                        :style="`view-transition-name: service-img-${service.id}`"
                    />
                    <Link :href="route('services.index')" class="absolute left-3 top-3 lg:hidden">
                        <Button variant="ghost" size="icon" class="h-9 w-9 rounded-full bg-card/90 text-foreground shadow">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <Badge class="absolute right-3 top-3 rounded-full bg-orange/90 px-2.5 py-1 text-[11px] font-bold text-white backdrop-blur-md">
                        {{ t('servicesPage.serviceLabel') }}
                    </Badge>
                </section>

                <section class="space-y-3 bg-card px-4 py-4 lg:col-span-7 lg:bg-transparent lg:p-0">
                    <p class="text-[13px] leading-none text-shop-light">
                        {{ t('serviceShow.breadcrumbHome') }} / {{ t('serviceShow.breadcrumbServices') }} / {{ service.name }}
                    </p>
                    <p class="text-[12px] font-semibold uppercase leading-none text-primary">
                        {{ service.category?.name || t('servicesPage.serviceLabel') }}
                    </p>
                    <h1 class="font-display text-2xl font-extrabold leading-tight text-foreground lg:text-3xl">{{ service.name }}</h1>
                    <p v-if="service.subtitle" class="text-[15px] leading-6 text-shop-muted">{{ service.subtitle }}</p>

                    <div class="flex items-center justify-between gap-3">
                        <p v-if="price" class="font-display text-3xl font-extrabold text-foreground">{{ price }}</p>
                        <p v-else class="font-display text-[18px] font-extrabold text-primary">{{ t('servicesPage.quoteOnlyPrice') }}</p>
                        <div v-if="service.city" class="flex items-center gap-1 text-[12px] text-shop-muted">
                            <MapPin class="h-3.5 w-3.5" />
                            {{ service.city }}
                        </div>
                    </div>

                    <StarRating
                        :rateable-id="service.id"
                        rateable-type="service"
                        :average-rating="Number(service.average_rating) || 0"
                        :ratings-count="service.ratings_count || 0"
                        :user-rating="service.user_rating || null"
                        size="md"
                    />

                    <div class="pt-1 lg:max-w-md">
                        <QuoteDialog v-if="isQuoteOnly" :service="service" />
                        <div v-else class="grid grid-cols-2 gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                class="h-12 rounded-[14px] border-primary bg-card text-[13px] font-bold text-primary shadow-none"
                                @click="orderService"
                            >
                                <ShoppingBag class="h-4 w-4" />
                                {{ t('servicesPage.orderButton') }}
                            </Button>
                            <Link :href="route('services.buy', { service: service.id })">
                                <Button class="h-12 w-full rounded-[14px] bg-primary text-[13px] font-bold text-white shadow-none">
                                    {{ t('servicesPage.buyButton') }}
                                </Button>
                            </Link>
                        </div>
                    </div>
                </section>
              </div>

              <div class="lg:mt-6 lg:grid lg:grid-cols-3 lg:gap-6">
                <section class="mt-2 bg-card px-4 py-4 lg:col-span-2 lg:mt-0 lg:rounded-xl lg:border lg:border-border lg:p-6">
                    <h2 class="font-display text-lg font-bold">{{ t('serviceShow.descriptionTitle') }}</h2>
                    <p class="mt-2 whitespace-pre-line text-[15px] leading-7 text-shop-muted">
                        {{ service.description || service.short_description || t('serviceShow.descriptionFallback') }}
                    </p>
                </section>

                <section v-if="service.shop" class="mt-2 bg-card px-4 py-4 lg:mt-0 lg:self-start lg:rounded-xl lg:border lg:border-border lg:p-6">
                    <h2 class="font-display text-lg font-bold">{{ t('serviceShow.providerTitle') }}</h2>
                    <Separator class="my-3" />
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="service.shop.logo"
                                :src="service.shop.logo"
                                :alt="service.shop.name"
                                class="h-10 w-10 rounded-xl object-cover"
                            />
                            <div>
                                <p class="text-[13px] font-bold">{{ service.shop.name }}</p>
                                <p class="text-[12px] text-shop-muted">{{ service.shop.city }}</p>
                            </div>
                        </div>
                        <Link :href="route('shops.show', { shop: service.shop.id })">
                            <Button variant="outline" class="h-9 rounded-full border-border bg-background px-4 text-sm font-bold text-primary shadow-none">
                                {{ t('serviceShow.viewPage') }}
                            </Button>
                        </Link>
                    </div>
                </section>
              </div>

              <div class="lg:mt-6 lg:overflow-hidden lg:rounded-xl lg:border lg:border-border [&>section]:lg:mt-0 [&>section]:lg:p-6">
                <ReviewsSection
                    rateable-type="service"
                    :rateable-id="service.id"
                    :reviews="service.reviews || []"
                    :average-rating="Number(service.average_rating) || 0"
                    :ratings-count="service.ratings_count || 0"
                    :user-rating="service.user_rating || null"
                />
              </div>

                <section v-if="relatedServices.length" class="space-y-3 px-3 py-5 lg:px-0 lg:py-8">
                    <h2 class="font-display text-xl font-extrabold">{{ t('serviceShow.relatedServices') }}</h2>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                        <ServiceCard v-for="related in relatedServices" :key="related.id" :service="related" />
                    </div>
                </section>
            </main>
        </Transition>

        <BottomNav />
    </div>
</template>
