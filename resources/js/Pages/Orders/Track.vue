<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Download, MessageCircle, XCircle, Clock, Camera } from 'lucide-vue-next';
import { computed } from 'vue';
import { ORDER_STATUS, money, datetime, relative, statusMeta } from '@/lib/orders';

const props = defineProps({
    order: Object,
    receipt: Object,
});

const status = computed(() => statusMeta(ORDER_STATUS, props.order.status));

// Étapes du parcours, pour la frise verticale.
const steps = computed(() => [
    { key: 'paid', label: 'Paiement confirmé', at: props.order.paid_at, done: props.order.is_paid },
    { key: 'proof', label: 'Livraison attestée par le vendeur', at: props.order.delivery_proof_at, done: props.order.has_delivery_proof },
    { key: 'confirmed', label: 'Réception confirmée', at: props.order.confirmed_at, done: props.order.status === 'delivered' },
]);

const confirmForm = useForm({});
const cancelForm = useForm({});
const declineForm = useForm({});

const confirm = () => confirmForm.post(route('orders.confirm', props.order.id), { preserveScroll: true });
const cancel = () => cancelForm.post(route('orders.cancel', props.order.id), { preserveScroll: true });
const decline = () => declineForm.post(route('orders.decline-cancellation', props.order.id), { preserveScroll: true });

// L'offre d'annulation ne vaut que tant que la commande est encore en cours.
const showCancellationChoice = computed(
    () => props.order.cancellation_offered && !props.order.cancellation_declined && props.order.status === 'in_progress',
);
</script>

<template>
    <Head :title="`Commande ${order.order_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-semibold leading-tight">Commande {{ order.order_number }}</h2>
                <Badge :variant="status.variant">{{ status.label }}</Badge>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

                <!-- Choix laissé au client après le délai sans livraison -->
                <div v-if="showCancellationChoice" class="rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950/40">
                    <div class="flex items-start gap-3">
                        <Clock class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                        <div class="flex-1">
                            <h3 class="font-medium text-amber-900 dark:text-amber-100">Cette commande n'est toujours pas livrée</h3>
                            <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                                Vous pouvez l'annuler et demander le remboursement de {{ money(order.total_price) }},
                                ou accorder un délai supplémentaire au vendeur.
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <Button variant="destructive" size="sm" :disabled="cancelForm.processing" @click="cancel">
                                    Annuler et me faire rembourser
                                </Button>
                                <Button variant="outline" size="sm" :disabled="declineForm.processing" @click="decline">
                                    Je patiente encore
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Délai accordé : échéance d'annulation automatique -->
                <div v-else-if="order.cancellation_declined && order.status === 'in_progress'"
                     class="rounded-lg border border-border bg-muted/40 p-4 text-sm text-muted-foreground">
                    Vous avez accordé un délai supplémentaire. Sans confirmation de réception,
                    la commande sera annulée et remboursée automatiquement
                    <strong class="text-foreground">{{ relative(order.auto_cancel_at) }}</strong>.
                </div>

                <div v-else-if="order.status === 'cancelled'" class="rounded-lg border border-destructive/40 bg-destructive/5 p-4">
                    <div class="flex items-start gap-3">
                        <XCircle class="mt-0.5 h-5 w-5 shrink-0 text-destructive" />
                        <div class="text-sm">
                            <p class="font-medium text-foreground">Commande annulée ({{ order.cancellation_reason }})</p>
                            <p v-if="order.refund" class="mt-1 text-muted-foreground">
                                Remboursement de {{ money(order.refund.amount) }} —
                                <span v-if="order.refund.status === 'pending'">en cours de traitement par l'administration.</span>
                                <span v-else-if="order.refund.status === 'refunded'">effectué.</span>
                                <span v-else>demande refusée, contactez le support.</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Suivi + reçu -->
                <div class="grid gap-6 md:grid-cols-5">
                    <div class="md:col-span-3 rounded-lg border border-border bg-card p-5">
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Suivi</h3>
                        <ol class="space-y-4">
                            <li v-for="step in steps" :key="step.key" class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <CheckCircle2 v-if="step.done" class="h-5 w-5 text-primary" />
                                    <div v-else class="h-5 w-5 rounded-full border-2 border-dashed border-muted-foreground/40" />
                                </div>
                                <div class="flex-1 -mt-0.5">
                                    <p class="text-sm" :class="step.done ? 'font-medium text-foreground' : 'text-muted-foreground'">
                                        {{ step.label }}
                                    </p>
                                    <p v-if="step.at" class="text-xs text-muted-foreground">{{ datetime(step.at) }}</p>
                                </div>
                            </li>
                        </ol>

                        <div v-if="order.delivery_proof_url" class="mt-5 border-t border-border pt-4">
                            <p class="mb-2 flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                <Camera class="h-3.5 w-3.5" /> Preuve de livraison du vendeur
                            </p>
                            <a :href="order.delivery_proof_url" target="_blank" rel="noopener">
                                <img :src="order.delivery_proof_url" alt="Preuve de livraison"
                                     class="max-h-52 rounded-md border border-border object-cover" />
                            </a>
                        </div>

                        <div v-if="order.can_confirm" class="mt-5 border-t border-border pt-4">
                            <Button class="w-full sm:w-auto" :disabled="confirmForm.processing" @click="confirm">
                                <CheckCircle2 class="mr-2 h-4 w-4" /> J'ai bien reçu ma commande
                            </Button>
                            <p class="mt-2 text-xs text-muted-foreground">
                                En confirmant, vous déclenchez le versement du paiement au vendeur.
                            </p>
                        </div>
                        <p v-else-if="order.status === 'in_progress'" class="mt-5 border-t border-border pt-4 text-xs text-muted-foreground">
                            Vous pourrez confirmer la réception dès que le vendeur aura déposé sa preuve de livraison.
                        </p>
                    </div>

                    <div class="md:col-span-2 space-y-4">
                        <div v-if="receipt" class="rounded-lg border border-border bg-card p-5">
                            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Reçu</h3>
                            <div class="space-y-2">
                                <a :href="receipt.download_url" class="flex w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                                    <Download class="h-4 w-4" /> Télécharger le PDF
                                </a>
                                <a v-if="receipt.whatsapp_vendor_url" :href="receipt.whatsapp_vendor_url" target="_blank" rel="noopener"
                                   class="flex w-full items-center justify-center gap-2 rounded-md border border-border px-4 py-2 text-sm font-medium hover:bg-muted">
                                    <MessageCircle class="h-4 w-4 text-green-600" /> Envoyer au vendeur
                                </a>
                                <a :href="receipt.whatsapp_url" target="_blank" rel="noopener"
                                   class="flex w-full items-center justify-center gap-2 rounded-md border border-border px-4 py-2 text-sm font-medium hover:bg-muted">
                                    <MessageCircle class="h-4 w-4 text-green-600" /> Partager sur WhatsApp
                                </a>
                            </div>
                            <p class="mt-3 text-xs text-muted-foreground">
                                Le lien partagé reste valable 30 jours et donne accès au reçu seul.
                            </p>
                        </div>

                        <div class="rounded-lg border border-border bg-card p-5 text-sm">
                            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Livraison</h3>
                            <p class="font-medium">{{ order.customer_name }}</p>
                            <p class="text-muted-foreground">{{ order.delivery_address }}</p>
                            <p class="text-muted-foreground">{{ order.phone_number }}</p>
                        </div>
                    </div>
                </div>

                <!-- Articles -->
                <div class="overflow-hidden rounded-lg border border-border bg-card">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="bg-muted/40">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Article</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Vendeur</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Qté</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="item in order.items" :key="item.id">
                                <td class="px-4 py-3">{{ item.name }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ item.vendor ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ item.quantity }}</td>
                                <td class="px-4 py-3 text-right">{{ money(item.price * item.quantity) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-muted/40">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right font-medium">Total payé</td>
                                <td class="px-4 py-3 text-right text-base font-semibold">{{ money(order.total_price) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <Link :href="route('orders.index')" class="inline-block text-sm text-primary hover:underline">
                    ← Toutes mes commandes
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
