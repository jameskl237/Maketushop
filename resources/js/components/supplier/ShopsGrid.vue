<script setup>
import EmptyState from '@/components/supplier/EmptyState.vue';
import ShopCard from '@/components/supplier/ShopCard.vue';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    shops: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    canCreateShop: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['create-shop', 'view-shop', 'edit-shop']);
const { t } = useI18n();
</script>

<template>
    <!-- Exemple:
      <ShopsGrid :shops="shops" @create-shop="openDialog" @view-shop="openShop" />
    -->
    <section :aria-label="t('supplier.shopsListAria')" class="space-y-4">
        <div v-if="loading" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <Card v-for="idx in 6" :key="`shop-skeleton-${idx}`">
                <CardContent class="space-y-3 p-5">
                    <div class="h-5 w-1/2 animate-pulse rounded bg-muted" />
                    <div class="h-3 w-full animate-pulse rounded bg-muted" />
                    <div class="h-3 w-2/3 animate-pulse rounded bg-muted" />
                    <div class="h-9 w-full animate-pulse rounded bg-muted" />
                </CardContent>
            </Card>
        </div>

        <EmptyState
            v-else-if="props.shops.length === 0"
            :title="t('supplier.emptyShopsTitle')"
            :description="t('supplier.emptyShopsDescription')"
            :action-label="t('supplier.createShop')"
            @action="props.canCreateShop && $emit('create-shop')"
        />

        <template v-else>
            <div class="flex items-center justify-between gap-4 rounded-xl border border-border/60 bg-card p-4">
                <p class="text-sm text-muted-foreground">
                    {{ t('supplier.availableShops', { count: props.shops.length }) }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <ShopCard
                    v-for="shop in props.shops"
                    :key="shop.id"
                    :shop="shop"
                    @view="$emit('view-shop', $event)"
                    @edit="$emit('edit-shop', $event)"
                />
            </div>
        </template>
    </section>
</template>
