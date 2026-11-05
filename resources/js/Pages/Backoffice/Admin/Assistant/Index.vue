<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/components/InputError.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    balance: { type: Number, default: null },
    lowBalanceThreshold: { type: Number, required: true },
    defaultQuota: { type: Number, required: true },
    creditsThisMonth: { type: Number, required: true },
    bots: { type: Object, required: true },
});

const OPERATORS = [
    { value: 'mtn', label: 'MTN' },
    { value: 'orange', label: 'Orange' },
    { value: 'moov', label: 'Moov' },
    { value: 'wave', label: 'Wave' },
];

const lowBalance = computed(() => props.balance !== null && props.balance < props.lowBalanceThreshold);
const number = (value) => new Intl.NumberFormat('fr-FR').format(value);

const recharge = useForm({ amount: '', operator: 'mtn', phone_number: '' });

const submitRecharge = () => {
    if (window.confirm(`Lancer une recharge de ${number(recharge.amount)} ? Ce paiement est réel.`)) {
        recharge.post(route('backoffice.admin.assistant.recharge'), {
            preserveScroll: true,
            onSuccess: () => recharge.reset(),
        });
    }
};

const updateQuota = (bot, value) => router.patch(
    route('backoffice.admin.assistant.quota', bot.id),
    { monthly_credit_quota: value === '' ? null : Number(value) },
    { preserveScroll: true },
);

const statusVariant = (status) => ({ active: 'default', error: 'destructive' }[status] ?? 'outline');
</script>

<template>
    <Head title="Assistant IA" />

    <AdminLayout title="Assistant IA" active-route="backoffice.admin.assistant.index">
        <template #content>
        <div class="space-y-5">
            <div v-if="lowBalance" class="rounded-lg border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive" role="alert">
                Solde bas : il reste {{ number(balance) }} crédits pour toutes les boutiques.
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Solde Botisimo</p>
                    <p class="mt-1 text-2xl font-semibold">{{ balance === null ? 'Indisponible' : number(balance) }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Crédits journalisés ce mois-ci</p>
                    <p class="mt-1 text-2xl font-semibold">{{ number(creditsThisMonth) }}</p>
                    <p class="text-xs text-muted-foreground">Réponses manuelles et campagnes des vendeurs.</p>
                </div>
            </div>

            <form class="space-y-3 rounded-lg border border-border bg-card p-4" @submit.prevent="submitRecharge">
                <h2 class="font-medium">Recharger par Mobile Money</h2>
                <div class="grid gap-3 sm:grid-cols-[1fr_10rem_1fr_auto] sm:items-end">
                    <div class="space-y-1">
                        <Label for="recharge-amount">Montant (minimum 500)</Label>
                        <Input id="recharge-amount" v-model="recharge.amount" type="number" min="500" required />
                        <InputError :message="recharge.errors.amount" />
                    </div>
                    <div class="space-y-1">
                        <Label for="recharge-operator">Opérateur</Label>
                        <select id="recharge-operator" v-model="recharge.operator" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option v-for="item in OPERATORS" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <Label for="recharge-phone">Numéro Mobile Money</Label>
                        <Input id="recharge-phone" v-model="recharge.phone_number" required />
                        <InputError :message="recharge.errors.phone_number" />
                    </div>
                    <Button type="submit" :disabled="recharge.processing">Recharger</Button>
                </div>
            </form>

            <div class="overflow-x-auto rounded-lg border border-border bg-card">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Boutique</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">WhatsApp</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Erreurs de sync</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Crédits du mois</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Quota</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-if="!bots.data.length">
                            <td colspan="6" class="px-4 py-6 text-center text-muted-foreground">Aucun assistant pour le moment.</td>
                        </tr>
                        <tr v-for="bot in bots.data" :key="bot.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3">
                                <span class="font-medium">{{ bot.shop_name }}</span>
                                <span class="block text-xs text-muted-foreground">{{ bot.vendor_name }}</span>
                                <span v-if="bot.last_error" class="block max-w-xs text-xs text-destructive">{{ bot.last_error }}</span>
                            </td>
                            <td class="px-4 py-3"><Badge :variant="statusVariant(bot.status)">{{ bot.status }}</Badge></td>
                            <td class="px-4 py-3">
                                {{ bot.whatsapp_phone_number ?? '—' }}
                                <span v-if="bot.quality_rating" class="block text-xs text-muted-foreground">{{ bot.quality_rating }}</span>
                            </td>
                            <td class="px-4 py-3 text-right" :class="bot.sync_errors_count ? 'text-destructive' : ''">{{ bot.sync_errors_count }}</td>
                            <td class="px-4 py-3 text-right">{{ number(bot.credits_month) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Input
                                    class="ml-auto w-28 text-right"
                                    type="number"
                                    min="0"
                                    :model-value="bot.monthly_credit_quota ?? ''"
                                    :placeholder="String(defaultQuota)"
                                    :aria-label="`Quota de ${bot.shop_name}`"
                                    @change="updateQuota(bot, $event.target.value)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="bots.links" />
        </div>
        </template>
    </AdminLayout>
</template>
