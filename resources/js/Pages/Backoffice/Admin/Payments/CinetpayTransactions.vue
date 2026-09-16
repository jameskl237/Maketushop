<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Head } from '@inertiajs/vue3';
import { money, datetime } from '@/lib/orders';

defineProps({ transactions: Object });

const variant = (status) =>
    ['ACCEPTED', 'VALIDATED', 'SUCCESS'].includes(status) ? 'default'
    : ['REFUSED', 'CANCELLED', 'FAILED'].includes(status) ? 'destructive'
    : 'secondary';
</script>

<template>
    <Head title="Transactions CinetPay" />

    <AdminLayout title="Transactions CinetPay">
        <div class="mx-auto max-w-6xl space-y-5 p-4 sm:p-6">
            <h1 class="text-xl font-semibold">Transactions CinetPay</h1>

            <div class="overflow-x-auto rounded-lg border border-border bg-card">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Transaction</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Client</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Moyen</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Montant</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Payé le</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="t in transactions.data" :key="t.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs">{{ t.transaction_id }}</span>
                                <span v-if="t.cpm_trans_id" class="block text-xs text-muted-foreground">op. {{ t.cpm_trans_id }}</span>
                            </td>
                            <td class="px-4 py-3">
                                {{ t.customer_name ?? '—' }}
                                <span class="block text-xs text-muted-foreground">{{ t.customer_phone }}</span>
                            </td>
                            <td class="px-4 py-3"><Badge :variant="variant(t.status)">{{ t.status }}</Badge></td>
                            <td class="px-4 py-3 text-muted-foreground">{{ t.payment_method ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ money(t.amount) }}</td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">{{ datetime(t.paid_at) }}</td>
                        </tr>
                        <tr v-if="!transactions.data.length">
                            <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">Aucune transaction CinetPay.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="transactions.links" :links="transactions.links" />
        </div>
    </AdminLayout>
</template>
