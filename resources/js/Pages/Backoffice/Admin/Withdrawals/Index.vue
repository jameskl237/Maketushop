<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, Send, X } from 'lucide-vue-next';
import { ref } from 'vue';
import { REQUEST_STATUS, money, datetime, statusMeta } from '@/lib/orders';

defineProps({
    requests: Object,
    currentStatus: String,
    statuses: Object,
    pendingTotal: Number,
});

const filter = (status) =>
    router.get(route('backoffice.admin.withdrawals.index'), { status }, { preserveState: true, replace: true });

const rejecting = ref(null);
const rejectForm = useForm({ reason: '' });
const actionForm = useForm({});

const approve = (id) => actionForm.post(route('backoffice.admin.withdrawals.approve', id), { preserveScroll: true });
const markPaid = (id) => actionForm.post(route('backoffice.admin.withdrawals.mark-paid', id), { preserveScroll: true });

const reject = (id) =>
    rejectForm.post(route('backoffice.admin.withdrawals.reject', id), {
        preserveScroll: true,
        onSuccess: () => { rejecting.value = null; rejectForm.reset(); },
    });
</script>

<template>
    <Head title="Demandes de retrait" />

    <AdminLayout title="Demandes de retrait">
        <div class="mx-auto max-w-5xl space-y-5 p-4 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-semibold">Demandes de retrait</h1>
                <div class="rounded-md border border-border bg-card px-4 py-2 text-sm">
                    En attente : <strong>{{ money(pendingTotal) }}</strong>
                </div>
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
                            <p class="mt-1 text-sm">{{ r.user?.name }} — {{ r.user?.email }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ r.phone_number }} ({{ r.operator }}) — demandé le {{ datetime(r.created_at) }}
                            </p>
                            <p v-if="r.admin_notes" class="mt-1 text-xs text-muted-foreground">Note : {{ r.admin_notes }}</p>
                        </div>

                        <div class="flex shrink-0 flex-wrap gap-2">
                            <template v-if="r.status === 'pending'">
                                <Button size="sm" :disabled="actionForm.processing" @click="approve(r.id)">
                                    <Check class="mr-1.5 h-4 w-4" /> Approuver
                                </Button>
                                <Button size="sm" variant="outline" @click="rejecting = rejecting === r.id ? null : r.id">
                                    <X class="mr-1.5 h-4 w-4" /> Refuser
                                </Button>
                            </template>
                            <Button v-else-if="r.status === 'approved'" size="sm" :disabled="actionForm.processing" @click="markPaid(r.id)">
                                <Send class="mr-1.5 h-4 w-4" /> Marquer comme versée
                            </Button>
                        </div>
                    </div>

                    <div v-if="rejecting === r.id" class="mt-3 border-t border-border pt-3">
                        <input v-model="rejectForm.reason" type="text" placeholder="Motif du refus (communiqué au vendeur)"
                               class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
                        <p v-if="rejectForm.errors.reason" class="mt-1 text-xs text-destructive">{{ rejectForm.errors.reason }}</p>
                        <div class="mt-2 flex gap-2">
                            <Button size="sm" variant="destructive" :disabled="rejectForm.processing" @click="reject(r.id)">
                                Confirmer le refus
                            </Button>
                            <Button size="sm" variant="ghost" @click="rejecting = null">Annuler</Button>
                        </div>
                        <p class="mt-2 text-xs text-muted-foreground">
                            Le montant bloqué est automatiquement remis sur le solde disponible du vendeur.
                        </p>
                    </div>
                </div>

                <Pagination v-if="requests.links" :links="requests.links" class="mt-6" />
            </div>
        </div>
    </AdminLayout>
</template>
