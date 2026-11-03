<script setup>
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, MapPin } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    shop: { type: Object, required: true },
});

const { t } = useI18n();

const initials = computed(() =>
    String(props.shop.name || 'MS')
        .split(' ')
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase(),
);
</script>

<template>
    <Card class="group h-full overflow-hidden rounded-xl border border-border bg-card shadow-none transition-all duration-200 hover:border-primary/40 hover:shadow-lg hover:shadow-black/5 active:scale-[0.98] dark:hover:shadow-black/40">
        <CardContent class="flex gap-4 p-4">
            <div class="relative shrink-0">
                <Avatar class="h-14 w-14 rounded-[18px] border border-border bg-mesh-gradient">
                    <AvatarImage :src="shop.logo_url || shop.logo" :alt="`Logo ${shop.name}`" class="object-cover" />
                    <AvatarFallback class="rounded-[18px] bg-mesh-gradient font-display text-base font-black text-white">
                        {{ initials }}
                    </AvatarFallback>
                </Avatar>
                <!-- Online Indicator -->
                <div class="absolute -bottom-1 -right-1 h-4 w-4 rounded-full border-2 border-card bg-shop-green shadow-sm"></div>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <h2 class="line-clamp-1 font-display text-[15px] font-black leading-tight text-foreground">{{ shop.name }}</h2>
                            <Badge v-if="shop.is_verified" class="h-4 w-4 rounded-full bg-primary p-0 flex items-center justify-center text-[8px] text-white">
                                ✓
                            </Badge>
                        </div>
                        <p class="mt-1 flex items-center gap-1 text-[12px] font-medium text-shop-muted">
                            <MapPin class="h-3 w-3 text-primary" />
                            {{ shop.city }}
                        </p>
                    </div>
                </div>

                <p class="mt-2 line-clamp-2 text-[13px] leading-relaxed text-shop-muted">
                    {{ shop.description || t('shopsPage.noDescription') }}
                </p>

                <div class="mt-4 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold text-shop-muted">{{ shop.products_count || 0 }} produits</span>
                    </div>

                    <Link :href="route('shops.show', { shop: shop.id })">
                        <Button variant="outline" class="h-9 rounded-full border-border bg-muted px-4 text-sm font-bold text-foreground transition-all group-hover:border-primary group-hover:bg-primary group-hover:text-white">
                            Visiter
                            <ArrowRight class="h-3 w-3" />
                        </Button>
                    </Link>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
