<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Head, Link } from '@inertiajs/vue3';
import { ORDER_STATUS, money, datetime, statusMeta } from '@/lib/orders';

defineProps({ orders: Object });
</script>

<template>
    <Head title="Commandes" />

    <AdminLayout title="Commandes">
        <div class="mx-auto max-w-6xl space-y-5 p-4 sm:p-6">
            <h1 class="text-xl font-semibold">Commandes</h1>

            <div class="overflow-x-auto rounded-lg border border-border bg-card">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Commande</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Client</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Statut</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Montant</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="order in orders.data" :key="order.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">{{ order.order_number }}</td>
                            <td class="px-4 py-3">
                                {{ order.customer_first_name }} {{ order.customer_last_name }}
                                <span class="block text-xs text-muted-foreground">{{ order.user?.email }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusMeta(ORDER_STATUS, order.status).variant">
                                    {{ statusMeta(ORDER_STATUS, order.status).label }}
                                </Badge>
                                <Badge v-if="order.is_paid" variant="outline" class="ml-1">Payée</Badge>
                            </td>
                            <td class="px-4 py-3 text-right font-medium">{{ money(order.total_price) }}</td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">{{ datetime(order.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('backoffice.admin.payments.order.show', order.id)" class="text-primary hover:underline">
                                    Détail
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!orders.data.length">
                            <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">Aucune commande.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="orders.links" :links="orders.links" />
        </div>
    </AdminLayout>
</template>
