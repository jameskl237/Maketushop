<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Head } from '@inertiajs/vue3';
import { PAYMENT_STATUS, money, datetime, statusMeta } from '@/lib/orders';

defineProps({ payments: Object });

// payable_type arrive en FQCN : on n'en garde que le nom de classe.
const payableLabel = (payment) => {
    const short = (payment.payable_type ?? '').split('\\').pop();
    return short === 'Order' ? 'Commande' : short === 'ShopSubscription' ? 'Abonnement' : short || '—';
};
</script>

<template>
    <Head title="Transactions" />

    <AdminLayout title="Transactions">
        <div class="mx-auto max-w-6xl space-y-5 p-4 sm:p-6">
            <h1 class="text-xl font-semibold">Transactions</h1>

            <div class="overflow-x-auto rounded-lg border border-border bg-card">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Transaction</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Objet</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Statut</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Montant</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Payé le</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="p in payments.data" :key="p.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs">{{ p.transaction_id }}</span>
                                <span class="block text-xs text-muted-foreground">{{ p.reference }}</span>
                            </td>
                            <td class="px-4 py-3">{{ payableLabel(p) }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusMeta(PAYMENT_STATUS, p.status).variant">
                                    {{ statusMeta(PAYMENT_STATUS, p.status).label }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right font-medium">{{ money(p.amount) }}</td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">{{ datetime(p.paid_at) }}</td>
                        </tr>
                        <tr v-if="!payments.data.length">
                            <td colspan="5" class="px-4 py-10 text-center text-muted-foreground">Aucune transaction.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="payments.links" :links="payments.links" />
        </div>
    </AdminLayout>
</template>
