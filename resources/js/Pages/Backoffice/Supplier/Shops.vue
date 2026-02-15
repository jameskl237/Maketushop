<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    shops: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head :title="$t('supplier.shops')" />

    <SupplierLayout
        :title="$t('supplier.shops')"
        :subtitle="$t('supplier.shopsSubtitle')"
        active-route="backoffice.supplier.shops.index"
        :can-create-shop="false"
    >
        <template #content>
            <Card>
                <CardHeader>
                    <CardTitle>{{ $t('supplier.shopsList') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="shops.length === 0" class="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                        {{ $t('supplier.noShops') }}
                    </div>

                    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div
                            v-for="shop in shops"
                            :key="shop.id"
                            class="rounded-lg border border-border p-4"
                        >
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h3 class="line-clamp-1 text-base font-semibold">{{ shop.name }}</h3>
                                <Badge variant="secondary">{{ shop.products_count }} {{ $t('supplier.products') }}</Badge>
                            </div>
                            <p class="line-clamp-2 text-sm text-muted-foreground">
                                {{ shop.description || $t('supplier.noDescription') }}
                            </p>
                            <p class="mt-2 text-xs text-muted-foreground">{{ shop.city }} - {{ shop.district }}</p>

                            <Link
                                :href="route('backoffice.supplier.shops.show', { shop: shop.id })"
                                class="mt-4 inline-block text-sm font-medium text-primary hover:underline"
                            >
                                {{ $t('supplier.viewProducts') }}
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </template>
    </SupplierLayout>
</template>


