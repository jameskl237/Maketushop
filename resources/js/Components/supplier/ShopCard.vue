<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import CopyShopLinkButton from '@/components/supplier/CopyShopLinkButton.vue';
import { Calendar, MapPin, Store } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    shop: {
        type: Object,
        required: true,
    },
});

defineEmits(['view', 'edit']);
const { t } = useI18n();

const formatDate = (value) => {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('fr-FR');
};
</script>

<template>
    <!-- Exemple: <ShopCard :shop="shop" @view="openShop(shop)" /> -->
    <Card class="h-full border-border/60 transition-all duration-300 hover:shadow-lg">
        <CardHeader class="py-3 px-3">
            <div class="mb-1 flex items-center justify-between">
                <Badge class="text-xs py-0.5 px-2" variant="secondary">{{ t('supplier.shopBadge') }}</Badge>
                <Store class="h-4 w-4 text-muted-foreground" />
            </div>
            <div class="flex items-center justify-between gap-2">
                <CardTitle class="line-clamp-1 text-sm">{{ props.shop.name }}</CardTitle>
                <CopyShopLinkButton :shop-id="props.shop.id" :shop-name="props.shop.name" />
            </div>
            <CardDescription class="line-clamp-2 text-sm mt-1">
                {{ props.shop.description || t('supplier.noShopDescription') }}
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-2 py-2 px-3">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <MapPin class="h-4 w-4" />
                <span class="text-sm">{{ props.shop.city }}{{ props.shop.district ? ' - ' + props.shop.district : '' }}</span>
            </div>
            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                <Calendar class="h-3.5 w-3.5" />
                <span class="text-xs">{{ t('supplier.createdAt', { date: formatDate(props.shop.created_at) }) }}</span>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-2">
                <Button class="w-full text-sm py-2" variant="outline" :aria-label="t('supplier.viewShopAria')" @click="$emit('view', props.shop)">
                    {{ t('supplier.view') }}
                </Button>
                <Button class="w-full text-sm py-2" variant="secondary" :aria-label="t('supplier.editShopAria')" @click="$emit('edit', props.shop)">
                    {{ t('supplier.edit') }}
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
