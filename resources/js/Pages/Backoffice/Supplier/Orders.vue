<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
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
    <Head title="Commandes" />

    <SupplierLayout
        title="Commandes"
        subtitle="Historique des commandes contenant tes produits"
        active-route="backoffice.supplier.orders.index"
        :can-create-shop="false"
    >
        <template #content>
            <Card>
                <CardHeader>
                    <CardTitle>Commandes reçues</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div v-if="orders.length === 0" class="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                        Aucune commande pour l'instant.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="order in orders"
                            :key="order.id"
                            class="rounded-lg border border-border p-4"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold">{{ order.order_number }}</p>
                                <Badge variant="secondary">{{ order.supplier_items_count }} article(s) fournisseur</Badge>
                            </div>

                            <p class="mt-1 text-sm text-muted-foreground">
                                Client: {{ order.customer_name || 'Inconnu' }} ({{ order.customer_email || 'email indisponible' }})
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Date: {{ formatDate(order.created_at) }} - Total commande: {{ order.total_price }} FCFA
                            </p>

                            <div class="mt-3 space-y-1 text-sm">
                                <p class="font-medium">Articles de tes boutiques :</p>
                                <div
                                    v-for="item in order.items"
                                    :key="`${order.id}-${item.id}`"
                                    class="flex flex-wrap items-center justify-between gap-2 rounded border border-border/70 px-3 py-2"
                                >
                                    <span>{{ item.name }} ({{ item.shop_name || 'Boutique' }})</span>
                                    <span class="text-muted-foreground">Qté: {{ item.quantity }} - {{ item.price }} FCFA</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </template>
    </SupplierLayout>
</template>


