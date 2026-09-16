<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowDownToLine, Clock, Wallet as WalletIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { REQUEST_STATUS, money, datetime, statusMeta } from '@/lib/orders';

const props = defineProps({
    stats: Object,
    transactions: Array,
    withdrawals: Object,
    minWithdrawal: { type: Number, default: 500 },
});

const form = useForm({
    amount: null,
    phone_number: '',
    operator: 'ORANGE_MONEY',
});

// Le serveur revalide, mais on évite d'envoyer une demande vouée à l'échec.
const canSubmit = computed(() =>
    form.amount >= props.minWithdrawal &&
    form.amount <= props.stats.available_balance &&
    form.phone_number.length >= 8,
);

const submit = () =>
    form.post(route('backoffice.supplier.wallet.withdrawal'), {
        preserveScroll: true,
        onSuccess: () => form.reset('amount'),
    });
</script>

<template>
    <Head title="Mon portefeuille" />

    <SupplierLayout>
        <div class="mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
            <h1 class="text-xl font-semibold">Mon portefeuille</h1>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Disponible</p>
                    <p class="mt-1 text-2xl font-semibold text-primary">{{ money(stats.available_balance) }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="flex items-center gap-1 text-xs uppercase tracking-wide text-muted-foreground">
                        <Clock class="h-3 w-3" /> En attente de livraison
                    </p>
                    <p class="mt-1 text-2xl font-semibold">{{ money(stats.pending_balance) }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Total gagné</p>
                    <p class="mt-1 text-2xl font-semibold">{{ money(stats.total_earned) }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Total retiré</p>
                    <p class="mt-1 text-2xl font-semibold">{{ money(stats.total_withdrawn) }}</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-5">
                <div class="lg:col-span-2 rounded-lg border border-border bg-card p-5">
                    <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                        <ArrowDownToLine class="h-4 w-4" /> Demander un retrait
                    </h2>

                    <form @submit.prevent="submit" class="space-y-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Montant (FCFA)</label>
                            <input v-model.number="form.amount" type="number" :min="minWithdrawal" :max="stats.available_balance"
                                   class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
                            <p class="mt-1 text-xs text-muted-foreground">
                                Minimum {{ money(minWithdrawal) }} — maximum {{ money(stats.available_balance) }}.
                            </p>
                            <p v-if="form.errors.amount" class="mt-1 text-xs text-destructive">{{ form.errors.amount }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Numéro mobile money</label>
                            <input v-model="form.phone_number" type="tel" placeholder="0708091011"
                                   class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
                            <p v-if="form.errors.phone_number" class="mt-1 text-xs text-destructive">{{ form.errors.phone_number }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Opérateur</label>
                            <select v-model="form.operator" class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm">
                                <option value="ORANGE_MONEY">Orange Money</option>
                                <option value="MTN_MOMO">MTN MoMo</option>
                                <option value="MOOV_MONEY">Moov Money</option>
                                <option value="WAVE">Wave</option>
                            </select>
                            <p v-if="form.errors.operator" class="mt-1 text-xs text-destructive">{{ form.errors.operator }}</p>
                        </div>

                        <Button type="submit" class="w-full" :disabled="!canSubmit || form.processing">
                            Envoyer la demande
                        </Button>
                        <p class="text-xs text-muted-foreground">
                            Le montant est bloqué dès l'envoi, puis versé après validation par l'administration.
                        </p>
                    </form>
                </div>

                <div class="lg:col-span-3 space-y-6">
                    <div class="rounded-lg border border-border bg-card">
                        <h2 class="border-b border-border px-5 py-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                            Mes demandes de retrait
                        </h2>
                        <div v-if="!withdrawals?.data?.length" class="p-5 text-sm text-muted-foreground">
                            Aucune demande pour l'instant.
                        </div>
                        <table v-else class="min-w-full divide-y divide-border text-sm">
                            <tbody class="divide-y divide-border">
                                <tr v-for="w in withdrawals.data" :key="w.id">
                                    <td class="px-5 py-3">
                                        <p class="font-medium">{{ money(w.amount) }}</p>
                                        <p class="text-xs text-muted-foreground">{{ datetime(w.created_at) }} — {{ w.phone_number }}</p>
                                        <p v-if="w.admin_notes" class="mt-0.5 text-xs text-destructive">{{ w.admin_notes }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <Badge :variant="statusMeta(REQUEST_STATUS, w.status).variant">
                                            {{ statusMeta(REQUEST_STATUS, w.status).label }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination v-if="withdrawals?.links" :links="withdrawals.links" class="p-4" />
                    </div>

                    <div class="rounded-lg border border-border bg-card">
                        <h2 class="border-b border-border px-5 py-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                            <WalletIcon class="h-4 w-4" /> Derniers mouvements
                        </h2>
                        <div v-if="!transactions?.length" class="p-5 text-sm text-muted-foreground">Aucun mouvement.</div>
                        <table v-else class="min-w-full divide-y divide-border text-sm">
                            <tbody class="divide-y divide-border">
                                <tr v-for="t in transactions" :key="t.id">
                                    <td class="px-5 py-2.5">
                                        <p>{{ t.description }}</p>
                                        <p class="text-xs text-muted-foreground">{{ datetime(t.created_at) }}</p>
                                    </td>
                                    <td class="px-5 py-2.5 text-right font-medium"
                                        :class="t.direction === 'debit' ? 'text-destructive' : 'text-primary'">
                                        {{ t.direction === 'debit' ? '−' : '+' }}{{ money(t.amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </SupplierLayout>
</template>
