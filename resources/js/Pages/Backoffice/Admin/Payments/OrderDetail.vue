<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Head, Link } from '@inertiajs/vue3';
import { ORDER_STATUS, PAYMENT_STATUS, money, datetime, statusMeta } from '@/lib/orders';

defineProps({ order: Object });
</script>

<template>
    <Head :title="`Commande ${order.order_number}`" />

    <AdminLayout :title="`Commande ${order.order_number}`">
        <div class="mx-auto max-w-4xl space-y-6 p-4 sm:p-6">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-xl font-semibold">Commande {{ order.order_number }}</h1>
                <Badge :variant="statusMeta(ORDER_STATUS, order.status).variant">
                    {{ statusMeta(ORDER_STATUS, order.status).label }}
                </Badge>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Total</p>
                    <p class="mt-1 text-xl font-semibold">{{ money(order.total_price) }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Commission</p>
                    <p class="mt-1 text-xl font-semibold">{{ money(order.platform_fee) }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Part vendeur</p>
                    <p class="mt-1 text-xl font-semibold">{{ money(order.vendor_amount) }}</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-lg border border-border bg-card p-5 text-sm">
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Client</h2>
                    <p class="font-medium">{{ order.customer_first_name }} {{ order.customer_last_name }}</p>
                    <p class="text-muted-foreground">{{ order.user?.email }}</p>
                    <p class="text-muted-foreground">{{ order.phone_number }}</p>
                    <p class="mt-2 text-muted-foreground">{{ order.delivery_address }}</p>
                </div>

                <div class="rounded-lg border border-border bg-card p-5 text-sm">
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Paiement</h2>
                    <div v-if="order.payment">
                        <Badge :variant="statusMeta(PAYMENT_STATUS, order.payment.status).variant">
                            {{ statusMeta(PAYMENT_STATUS, order.payment.status).label }}
                        </Badge>
                        <dl class="mt-3 space-y-1">
                            <div class="flex justify-between gap-3"><dt class="text-muted-foreground">Transaction</dt><dd class="truncate">{{ order.payment.transaction_id }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-muted-foreground">Moyen</dt><dd>{{ order.payment.payment_method ?? '—' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-muted-foreground">Payé le</dt><dd>{{ datetime(order.payment.paid_at) }}</dd></div>
                        </dl>
                    </div>
                    <p v-else class="text-muted-foreground">Aucun paiement enregistré.</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-lg border border-border bg-card p-5 text-sm">
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Livraison</h2>
                    <dl class="space-y-1">
                        <div class="flex justify-between gap-3"><dt class="text-muted-foreground">Preuve déposée</dt><dd>{{ datetime(order.delivery_proof_at) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-muted-foreground">Confirmée par le client</dt><dd>{{ datetime(order.client_confirmed_at) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-muted-foreground">Escrow libéré</dt><dd>{{ datetime(order.escrow_released_at) }}</dd></div>
                    </dl>
                </div>

                <div v-if="order.cancelled_at" class="rounded-lg border border-destructive/40 bg-destructive/5 p-5 text-sm">
                    <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Annulation</h2>
                    <p>{{ order.cancellation_reason }}</p>
                    <p class="text-muted-foreground">{{ datetime(order.cancelled_at) }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-border bg-card">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Article</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Boutique</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Qté</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Prix</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="p in order.products" :key="p.id">
                            <td class="px-4 py-3">{{ p.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ p.shop?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ p.pivot?.quantity ?? 1 }}</td>
                            <td class="px-4 py-3 text-right">{{ money(p.pivot?.price ?? p.price) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Link :href="route('backoffice.admin.payments.orders')" class="inline-block text-sm text-primary hover:underline">
                ← Toutes les commandes
            </Link>
        </div>
    </AdminLayout>
</template>
