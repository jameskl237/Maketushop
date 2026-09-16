<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Check, X } from 'lucide-vue-next';
import { ref } from 'vue';
import { REQUEST_STATUS, money, datetime, statusMeta } from '@/lib/orders';

const props = defineProps({
    requests: Object,
    currentStatus: String,
    statuses: Object,
    pendingTotal: Number,
});

const open = ref(null);
const form = useForm({ admin_notes: '' });

const filter = (status) =>
    router.get(route('backoffice.admin.refunds.index'), { status }, { preserveState: true, replace: true });

const act = (id, action) =>
    form.post(route(`backoffice.admin.refunds.${action}`, id), {
        preserveScroll: true,
        onSuccess: () => { open.value = null; form.reset(); },
    });
</script>

<template>
    <Head title="Remboursements" />

    <AdminLayout title="Remboursements">
        <div class="mx-auto max-w-5xl space-y-5 p-4 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-semibold">Remboursements</h1>
                <div class="rounded-md border border-border bg-card px-4 py-2 text-sm">
                    Reste à rembourser : <strong>{{ money(pendingTotal) }}</strong>
                </div>
            </div>

            <div class="rounded-md border border-amber-300 bg-amber-50 p-3 text-xs text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100">
                Le virement s'effectue depuis le back-office CinetPay. Marquez ensuite la demande comme remboursée ici.
            </div>

            <div class="flex flex-wrap gap-2">
                <button v-for="(label, key) in statuses" :key="key" type="button" @click="filter(key)"
                        class="rounded-full border px-3 py-1 text-sm transition"
                        :class="currentStatus === key ? 'border-primary bg-primary text-primary-foreground' : 'border-border hover:bg-muted'">
                    {{ label }}
                </button>
            </div>

            <div v-if="!requests.data.length" class="rounded-lg border border-border bg-card p-10 text-center text-sm text-muted-foreground">
                Aucune demande pour ce filtre.
            </div>

            <div v-else class="space-y-3">
                <div v-for="r in requests.data" :key="r.id" class="rounded-lg border border-border bg-card p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-lg font-semibold">{{ money(r.amount) }}</span>
                                <Badge :variant="statusMeta(REQUEST_STATUS, r.status).variant">
                                    {{ statusMeta(REQUEST_STATUS, r.status).label }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-sm">
                                Commande
                                <Link v-if="r.order_id" :href="route('backoffice.admin.payments.order.show', r.order_id)" class="text-primary hover:underline">
                                    {{ r.order_number }}
                                </Link>
                                <span v-else>{{ r.order_number ?? '—' }}</span>
                                — {{ r.customer }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ r.reason }} — demandé le {{ datetime(r.created_at) }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                À rembourser sur {{ r.phone_number ?? '—' }}
                                <span v-if="r.transaction_id">— transaction {{ r.transaction_id }}</span>
                            </p>
                            <p v-if="r.admin_notes" class="mt-1 text-xs text-muted-foreground">Note : {{ r.admin_notes }}</p>
                        </div>

                        <div v-if="r.status === 'pending'" class="flex shrink-0 flex-wrap gap-2">
                            <Button size="sm" @click="open = open === `ok-${r.id}` ? null : `ok-${r.id}`">
                                <Check class="mr-1.5 h-4 w-4" /> Marquer remboursée
                            </Button>
                            <Button size="sm" variant="outline" @click="open = open === `no-${r.id}` ? null : `no-${r.id}`">
                                <X class="mr-1.5 h-4 w-4" /> Refuser
                            </Button>
                        </div>
                    </div>

                    <div v-if="open === `ok-${r.id}` || open === `no-${r.id}`" class="mt-3 border-t border-border pt-3">
                        <input v-model="form.admin_notes" type="text"
                               :placeholder="open === `ok-${r.id}` ? 'Référence du virement (facultatif)' : 'Motif du refus (obligatoire)'"
                               class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
                        <p v-if="form.errors.admin_notes" class="mt-1 text-xs text-destructive">{{ form.errors.admin_notes }}</p>
                        <div class="mt-2 flex gap-2">
                            <Button v-if="open === `ok-${r.id}`" size="sm" :disabled="form.processing" @click="act(r.id, 'refunded')">
                                Confirmer le remboursement
                            </Button>
                            <Button v-else size="sm" variant="destructive" :disabled="form.processing" @click="act(r.id, 'reject')">
                                Confirmer le refus
                            </Button>
                            <Button size="sm" variant="ghost" @click="open = null">Annuler</Button>
                        </div>
                    </div>
                </div>

                <Pagination v-if="requests.links" :links="requests.links" class="mt-6" />
            </div>
        </div>
    </AdminLayout>
</template>
