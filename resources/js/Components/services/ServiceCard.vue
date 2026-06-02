<script setup>
import FavoriteButton from '@/components/products/shared/FavoriteButton.vue';
import StarRating from '@/components/StarRating.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useCart } from '@/composables/useCart';
import { Link } from '@inertiajs/vue3';
import { FileText, Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    service: { type: Object, required: true },
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

const onAddToCart = () => {
    addToCart({ ...props.service, type: 'service' }, 1);
};
</script>

<template>
    <Card class="group overflow-hidden rounded-[24px] border-none bg-transparent shadow-none transition-all duration-300 active:scale-[0.98]">
        <CardContent class="p-0">
            <Link :href="route('services.show', { service: service.id })" class="block">
                <div class="relative aspect-square overflow-hidden rounded-[24px] bg-gradient-to-br from-[#FFE9EE] to-[#FFF1E6] shadow-sm group-hover:shadow-xl transition-shadow duration-300">
                    <img
                        :src="service.main_image || '/images/Maketu1.png'"
                        :alt="service.name"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                        loading="lazy"
                        :style="`view-transition-name: service-img-${service.id}`"
                    />
                    <div class="absolute inset-0 ring-1 ring-inset ring-white/20"></div>

                    <div class="absolute left-3 top-3 flex flex-col gap-1.5">
                        <Badge v-if="service.is_new" class="h-6 rounded-lg bg-primary/90 px-2.5 text-[10px] font-bold text-white backdrop-blur-md">
                            New
                        </Badge>
                        <Badge class="h-6 rounded-lg bg-orange/90 px-2.5 text-[10px] font-bold text-white backdrop-blur-md">
                            <Sparkles class="mr-1 h-3 w-3" /> Service
                        </Badge>
                    </div>

                    <div class="absolute right-3 top-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/70 backdrop-blur-md transition-colors hover:bg-white">
                            <FavoriteButton :item-id="service.id" type="service" />
                        </div>
                    </div>

                    <!-- Glass Price Capsule -->
                    <div class="absolute bottom-3 right-3 rounded-xl border border-white/40 bg-white/70 px-3 py-1.5 backdrop-blur-md shadow-lg shadow-black/5">
                        <p v-if="price" class="font-display text-[13px] font-black leading-none text-primary">{{ price }}</p>
                        <p v-else class="font-display text-[11px] font-black leading-none text-primary">Sur devis</p>
                    </div>
                </div>
            </Link>

            <div class="space-y-2 px-1.5 py-3">
                <div class="flex items-center justify-between gap-2">
                    <p class="line-clamp-1 text-[10px] font-bold uppercase tracking-wider text-shop-light">
                        {{ service.category?.name || 'Service' }}
                    </p>
                    <StarRating
                        :average-rating="Number(service.average_rating) || 0"
                        :ratings-count="service.ratings_count || 0"
                        readonly
                        size="sm"
                    />
                </div>

                <Link
                    :href="route('services.show', { service: service.id })"
                    class="line-clamp-2 min-h-[36px] font-display text-[14px] font-bold leading-snug text-foreground transition-colors group-hover:text-primary"
                >
                    {{ service.name }}
                </Link>

                <div class="flex items-center justify-between gap-2 border-t border-border pt-2">
                    <p class="line-clamp-1 text-[11px] font-medium text-shop-light">{{ service.shop?.name }}</p>
                    <p v-if="service.city" class="whitespace-nowrap text-[10px] text-shop-light/70">{{ service.city }}</p>
                </div>

                <div class="pt-1">
                    <Link v-if="isQuoteOnly" :href="route('services.show', { service: service.id })" class="block w-full">
                        <Button variant="default" class="h-9 w-full rounded-xl px-1 text-[11px] font-bold">
                            <FileText class="h-3.5 w-3.5" />
                            Demander un devis
                        </Button>
                    </Link>
                    <div v-else class="grid grid-cols-2 gap-2">
                        <Button
                            variant="glass"
                            class="h-9 rounded-xl px-1 text-[11px] font-bold"
                            @click="onAddToCart"
                        >
                            Commander
                        </Button>
                        <Link :href="route('services.buy', { service: service.id })" class="w-full">
                            <Button variant="default" class="h-9 w-full rounded-xl px-1 text-[11px] font-bold">
                                Acheter
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
