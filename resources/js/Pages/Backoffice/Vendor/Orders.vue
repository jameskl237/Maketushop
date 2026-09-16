<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, PackageSearch } from 'lucide-vue-next';
import { money, datetime } from '@/lib/orders';

const props = defineProps({
    orders: Object,
    currentStatus: String,
    statuses: Object,
});

const filter = (status) =>
    router.get(route('backoffice.supplier.orders.index'), status ? { status } : {}, {
        preserveState: true,
        replace: true,
    });

// Commande payée sans preuve déposée : le vendeur bloque son propre paiement.
const awaitingProof = (order) => order.is_paid && !order.delivery_proof_path && order.status === 'in_progress';
</script>

<template>
    <Head title="Commandes reçues" />

    <SupplierLayout>
        <div class="mx-auto max-w-6xl space-y-5 p-4 sm:p-6">
            <h1 class="text-xl font-semibold">Commandes reçues</h1>

            <div class="flex flex-wrap gap-2">
                <button type="button" @click="filter(null)"
                        class="rounded-full border px-3 py-1 text-sm transition"
                        :class="!currentStatus ? 'border-primary bg-primary text-primary-foreground' : 'border-border hover:bg-muted'">
                    Toutes
                </button>
                <button v-for="(label, key) in statuses" :key="key" type="button" @click="filter(key)"
                        class="rounded-full border px-3 py-1 text-sm transition"
                        :class="currentStatus === key ? 'border-primary bg-primary text-primary-foreground' : 'border-border hover:bg-muted'">
                    {{ label }}
                </button>
            </div>

            <div v-if="!orders.data.length" class="rounded-lg border border-border bg-card p-10 text-center">
                <PackageSearch class="mx-auto h-10 w-10 text-muted-foreground" />
                <p class="mt-3 text-sm text-muted-foreground">Aucune commande pour ce filtre.</p>
            </div>

            <div v-else class="space-y-3">
                <Link v-for="order in orders.data" :key="order.id"
                      :href="route('backoffice.supplier.orders.show', order.id)"
                      class="block rounded-lg border border-border bg-card p-4 transition hover:border-primary/50 hover:shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ order.order_number }}</span>
                                <Badge v-if="order.is_paid" variant="default">Payée</Badge>
                                <Badge v-else variant="secondary">En attente de paiement</Badge>
                                <span v-if="awaitingProof(order)"
                                      class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                                    <AlertTriangle class="h-3 w-3" /> Photo de livraison attendue
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ order.customer_first_name }} {{ order.customer_last_name }} — {{ datetime(order.created_at) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-base font-semibold">{{ money(order.vendor_amount || order.total_price) }}</p>
                            <p class="text-xs text-muted-foreground">votre part</p>
                        </div>
                    </div>
                </Link>

                <Pagination v-if="orders.links" :links="orders.links" class="mt-6" />
            </div>
        </div>
    </SupplierLayout>
</template>
