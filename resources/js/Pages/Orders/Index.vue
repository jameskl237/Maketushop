<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Head, Link } from '@inertiajs/vue3';
import { AlertCircle, PackageSearch } from 'lucide-vue-next';
import { ORDER_STATUS, money, datetime, statusMeta } from '@/lib/orders';

defineProps({ orders: Object });

// Une commande réclame une action du client : confirmer, ou arbitrer l'annulation.
const needsAction = (order) =>
    order.can_confirm || (order.cancellation_offered && !order.cancellation_declined && order.status === 'in_progress');
</script>

<template>
    <Head title="Mes commandes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">Mes commandes</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div v-if="!orders.data.length" class="rounded-lg border border-border bg-card p-10 text-center">
                    <PackageSearch class="mx-auto h-10 w-10 text-muted-foreground" />
                    <p class="mt-3 text-sm text-muted-foreground">Vous n'avez pas encore de commande.</p>
                    <Link :href="route('products.index')" class="mt-4 inline-block text-sm text-primary hover:underline">
                        Parcourir les produits
                    </Link>
                </div>

                <div v-else class="space-y-3">
                    <Link v-for="order in orders.data" :key="order.id"
                          :href="route('orders.track', order.id)"
                          class="block rounded-lg border border-border bg-card p-4 transition hover:border-primary/50 hover:shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium">{{ order.order_number }}</span>
                                    <Badge :variant="statusMeta(ORDER_STATUS, order.status).variant">
                                        {{ statusMeta(ORDER_STATUS, order.status).label }}
                                    </Badge>
                                    <span v-if="needsAction(order)"
                                          class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                                        <AlertCircle class="h-3 w-3" /> Action requise
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ order.total_products }} article(s) — {{ datetime(order.created_at) }}
                                </p>
                            </div>
                            <span class="text-base font-semibold">{{ money(order.total_price) }}</span>
                        </div>
                    </Link>

                    <Pagination v-if="orders.links" :links="orders.links" class="mt-6" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
