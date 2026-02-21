<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import CopyShopLinkButton from '@/components/supplier/CopyShopLinkButton.vue';
import { Calendar, MapPin, Store } from 'lucide-vue-next';

const props = defineProps({
    shop: {
        type: Object,
        required: true,
    },
});

defineEmits(['view', 'edit']);

const formatDate = (value) => {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('fr-FR');
};
</script>

<template>
    <!-- Exemple: <ShopCard :shop="shop" @view="openShop(shop)" /> -->
    <Card class="h-full border-border/60 transition-all duration-300 hover:shadow-lg">
        <CardHeader>
            <div class="mb-2 flex items-center justify-between">
                <Badge variant="secondary">Boutique</Badge>
                <Store class="h-4 w-4 text-muted-foreground" />
            </div>
            <div class="flex items-center justify-between gap-2">
                <CardTitle class="line-clamp-1">{{ props.shop.name }}</CardTitle>
                <CopyShopLinkButton :shop-id="props.shop.id" :shop-name="props.shop.name" />
            </div>
            <CardDescription class="line-clamp-2">
                {{ props.shop.description || 'Aucune description pour le moment.' }}
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-3">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <MapPin class="h-4 w-4" />
                <span>{{ props.shop.city }} - {{ props.shop.district }}</span>
            </div>
            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                <Calendar class="h-3.5 w-3.5" />
                <span>Créée le {{ formatDate(props.shop.created_at) }}</span>
            </div>
            <div class="grid grid-cols-2 gap-2">
            <Button class="w-full" variant="outline" aria-label="Voir la boutique" @click="$emit('view', props.shop)">
                    Voir
                </Button>
                <Button class="w-full" variant="secondary" aria-label="Modifier la boutique" @click="$emit('edit', props.shop)">
                    Modifier
            </Button>
            </div>
        </CardContent>
    </Card>
</template>

