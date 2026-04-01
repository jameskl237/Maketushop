<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import CopyShopLinkButton from '@/components/supplier/CopyShopLinkButton.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    products: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head :title="$t('supplier.products')" />

    <SupplierLayout
        :title="$t('supplier.products')"
        :subtitle="$t('supplier.productsSubtitle')"
        active-route="backoffice.supplier.products.index"
        :can-create-shop="false"
    >
        <template #content>
            <Card>
                <CardHeader>
                    <CardTitle>{{ $t('supplier.catalog') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="products.length === 0" class="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                        {{ $t('supplier.noProducts') }}
                    </div>

                    <div v-else class="w-full overflow-x-auto rounded-lg border border-border">
                        <table class="min-w-[850px] text-sm">
                            <thead class="bg-muted/40">
                                <tr class="text-left">
                                    <th class="px-4 py-3 font-medium">{{ $t('supplier.code') }}</th>
                                    <th class="px-4 py-3 font-medium">{{ $t('supplier.name') }}</th>
                                    <th class="px-4 py-3 font-medium">{{ $t('supplier.shop') }}</th>
                                    <th class="px-4 py-3 font-medium">{{ $t('supplier.category') }}</th>
                                    <th class="px-4 py-3 font-medium">{{ $t('supplier.price') }}</th>
                                    <th class="px-4 py-3 font-medium">{{ $t('supplier.stock') }}</th>
                                    <th class="px-4 py-3 font-medium">{{ $t('supplier.medias') }}</th>
                                    <th class="px-4 py-3 font-medium">{{ $t('supplier.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="product in products"
                                    :key="product.id"
                                    class="border-t border-border"
                                >
                                    <td class="px-4 py-3">{{ product.code }}</td>
                                    <td class="px-4 py-3 font-medium">{{ product.name }}</td>
                                    <td class="px-4 py-3">
                                        <div v-if="product.shop?.name" class="flex items-center gap-1.5">
                                            <span class="line-clamp-1">{{ product.shop.name }}</span>
                                            <CopyShopLinkButton :shop-id="product.shop_id" :shop-name="product.shop.name" />
                                        </div>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="px-4 py-3">{{ product.category?.name || $t('supplier.noCategory') }}</td>
                                    <td class="px-4 py-3">{{ product.price }} FCFA</td>
                                    <td class="px-4 py-3">
                                        <Badge :variant="product.in_stock ? 'secondary' : 'outline'">
                                            {{ product.in_stock ? $t('supplier.inStock') : $t('supplier.outOfStock') }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3">{{ product.medias_count ?? 0 }}</td>
                                    <td class="px-4 py-3">
                                        <Link
                                            :href="route('backoffice.supplier.shops.products.show', { shop: product.shop_id, product: product.id })"
                                            class="text-primary hover:underline"
                                        >
                                            {{ $t('supplier.open') }}
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </template>
    </SupplierLayout>
</template>


