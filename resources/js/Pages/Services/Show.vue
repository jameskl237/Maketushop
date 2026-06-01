<script setup>
import ServiceCard from '@/components/services/ServiceCard.vue';
import QuoteDialog from '@/components/services/QuoteDialog.vue';
import BottomNav from '@/components/shop/BottomNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCart } from '@/composables/useCart';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, MapPin, ShoppingBag, Star } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    service: { type: Object, required: true },
    relatedServices: { type: Array, default: () => [] },
});

const { addToCart } = useCart();

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
    <Head :title="service.name" />

    <div class="min-h-screen bg-shop-bg pb-16 text-foreground">
        <Transition name="page-fade" mode="out-in">
            <main class="mx-auto max-w-3xl">
                <section class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-[#FFE9EE] to-[#FFF1E6]">
                    <img
                        :src="service.main_image || service.images?.[0]?.url || '/images/Maketu1.png'"
                        :alt="service.name"
                        class="h-full w-full object-cover"
                        loading="lazy"
                        :style="`view-transition-name: service-img-${service.id}`"
                    />
                    <Link :href="route('services.index')" class="absolute left-3 top-3">
                        <Button variant="ghost" size="icon" class="h-9 w-9 rounded-[12px] bg-white/90 shadow-none">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <Badge class="absolute right-3 top-3 rounded-full bg-orange/90 px-2.5 py-1 text-[10px] font-bold text-white backdrop-blur-md">
                        Service
                    </Badge>
                </section>

                <section class="space-y-3 bg-white px-4 py-3.5">
                    <p class="text-[10px] leading-none text-shop-light">
                        Accueil / Services / {{ service.name }}
                    </p>
                    <p class="text-[10.5px] font-semibold uppercase leading-none text-primary">
                        {{ service.category?.name || 'Service' }}
                    </p>
                    <h1 class="font-display text-xl font-extrabold leading-6 text-foreground">{{ service.name }}</h1>
                    <p v-if="service.subtitle" class="text-[12px] leading-5 text-shop-muted">{{ service.subtitle }}</p>

                    <div class="flex items-center justify-between gap-3">
                        <p v-if="price" class="font-display text-[22px] font-extrabold text-primary">{{ price }}</p>
                        <p v-else class="font-display text-[18px] font-extrabold text-primary">Sur devis</p>
                        <div v-if="service.city" class="flex items-center gap-1 text-[11px] text-shop-muted">
                            <MapPin class="h-3.5 w-3.5" />
                            {{ service.city }}
                        </div>
                    </div>

                    <div class="flex items-center gap-1 text-[10px] text-shop-amber">
                        <Star class="h-3.5 w-3.5 fill-current" />
                        <span>{{ service.average_rating || '0.0' }}</span>
                        <span class="text-shop-light">({{ service.ratings_count || 0 }} avis)</span>
                    </div>

                    <div class="pt-1">
                        <QuoteDialog v-if="isQuoteOnly" :service="service" />
                        <div v-else class="grid grid-cols-2 gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                class="h-12 rounded-[14px] border-primary bg-white text-[12px] font-bold text-primary shadow-none"
                                @click="orderService"
                            >
                                <ShoppingBag class="h-4 w-4" />
                                Commander
                            </Button>
                            <Link :href="route('services.buy', { service: service.id })">
                                <Button class="h-12 w-full rounded-[14px] bg-gradient-to-r from-primary to-orange text-[12px] font-bold text-white shadow-none">
                                    Acheter
                                </Button>
                            </Link>
                        </div>
                    </div>
                </section>

                <section class="mt-2 bg-white px-4 py-3.5">
                    <h2 class="font-display text-[16px] font-bold">Description</h2>
                    <p class="mt-2 whitespace-pre-line text-[12px] leading-5 text-shop-muted">
                        {{ service.description || service.short_description || 'Aucune description fournie pour ce service.' }}
                    </p>
                </section>

                <section v-if="service.shop" class="mt-2 bg-white px-4 py-3.5">
                    <h2 class="font-display text-[16px] font-bold">Prestataire</h2>
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
                                <p class="text-[11px] text-shop-muted">{{ service.shop.city }}</p>
                            </div>
                        </div>
                        <Link :href="route('shops.show', { shop: service.shop.id })">
                            <Button variant="outline" class="h-[30px] rounded-[10px] border-border bg-shop-bg text-[11px] font-bold text-primary shadow-none">
                                Voir la page
                            </Button>
                        </Link>
                    </div>
                </section>

                <section v-if="relatedServices.length" class="space-y-3 px-3 py-4">
                    <h2 class="font-display text-[17px] font-extrabold">Services similaires</h2>
                    <div class="grid grid-cols-2 gap-2.5">
                        <ServiceCard v-for="related in relatedServices" :key="related.id" :service="related" />
                    </div>
                </section>
            </main>
        </Transition>

        <BottomNav />
    </div>
</template>
