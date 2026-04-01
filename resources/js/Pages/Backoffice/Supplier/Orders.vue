<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import CopyShopLinkButton from '@/components/supplier/CopyShopLinkButton.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head } from '@inertiajs/vue3';

defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
});

const formatDate = (value) => new Date(value).toLocaleDateString('fr-FR');
</script>

<template>
    <Head :title="$t('supplier.orders')" />

    <SupplierLayout
        :title="$t('supplier.orders')"
        :subtitle="$t('supplier.ordersSubtitle')"
        active-route="backoffice.supplier.orders.index"
        :can-create-shop="false"
    >
        <template #content>
            <Card>
                <CardHeader>
                    <CardTitle>{{ $t('supplier.receivedOrders') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div v-if="orders.length === 0" class="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                        {{ $t('supplier.noOrders') }}
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="order in orders"
                            :key="order.id"
                            class="rounded-lg border border-border p-4"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold">{{ order.order_number }}</p>
                                <Badge variant="secondary">{{ order.supplier_items_count }} {{ $t('supplier.supplierItems') }}</Badge>
                            </div>

                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ $t('supplier.customer') }}: {{ order.customer_name || $t('supplier.unknown') }} ({{ order.customer_email || $t('supplier.emailUnavailable') }})
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ $t('supplier.date') }}: {{ formatDate(order.created_at) }} - {{ $t('supplier.orderTotal') }}: {{ order.total_price }} FCFA
                            </p>

                            <div class="mt-3 space-y-1 text-sm">
                                <p class="font-medium">{{ $t('supplier.itemsFromYourShops') }}</p>
                                <div
                                    v-for="item in order.items"
                                    :key="`${order.id}-${item.id}`"
                                    class="flex flex-wrap items-center justify-between gap-2 rounded border border-border/70 px-3 py-2"
                                >
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span>{{ item.name }} ({{ item.shop_name || $t('supplier.shop') }})</span>
                                        <CopyShopLinkButton
                                            v-if="item.shop_id && item.shop_name"
                                            :shop-id="item.shop_id"
                                            :shop-name="item.shop_name"
                                        />
                                    </div>
                                    <span class="text-muted-foreground">{{ $t('supplier.quantity') }}: {{ item.quantity }} - {{ item.price }} FCFA</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </template>
    </SupplierLayout>
</template>


