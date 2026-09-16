<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Camera, CheckCircle2, Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { ORDER_STATUS, money, datetime, statusMeta } from '@/lib/orders';

const props = defineProps({
    order: Object,
    delivery: Object,
});

const status = computed(() => statusMeta(ORDER_STATUS, props.delivery.status));
const preview = ref(null);

const form = useForm({ proof: null });

const pickFile = (event) => {
    const file = event.target.files?.[0] ?? null;
    form.proof = file;
    preview.value = file ? URL.createObjectURL(file) : null;
};

const submit = () => {
    form.post(route('backoffice.supplier.orders.deliver', props.order.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => { form.reset('proof'); preview.value = null; },
    });
};
</script>

<template>
    <Head :title="`Commande ${order.order_number}`" />

    <SupplierLayout>
        <div class="mx-auto max-w-4xl space-y-6 p-4 sm:p-6">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-xl font-semibold">Commande {{ order.order_number }}</h1>
                <Badge :variant="status.variant">{{ status.label }}</Badge>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-lg border border-border bg-card p-5 text-sm">
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Client</h2>
                    <p class="font-medium">{{ order.customer_first_name }} {{ order.customer_last_name }}</p>
                    <p class="text-muted-foreground">{{ order.delivery_address }}</p>
                    <p class="text-muted-foreground">{{ order.phone_number }}</p>
                    <p class="mt-3 text-xs text-muted-foreground">Total commande : {{ money(order.total_price) }}</p>
                    <p class="text-xs text-muted-foreground">Votre part : {{ money(order.vendor_amount) }}</p>
                </div>

                <div class="rounded-lg border border-border bg-card p-5">
                    <h2 class="mb-3 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        <Camera class="h-3.5 w-3.5" /> Preuve de livraison
                    </h2>

                    <div v-if="delivery.client_confirmed_at" class="flex items-start gap-2 rounded-md bg-muted/50 p-3 text-sm">
                        <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                        <span>Réception confirmée par le client le {{ datetime(delivery.client_confirmed_at) }}. Le paiement vous a été versé.</span>
                    </div>

                    <template v-else-if="delivery.is_paid">
                        <div v-if="delivery.has_proof" class="mb-3">
                            <img :src="delivery.proof_url" alt="Preuve de livraison"
                                 class="max-h-44 rounded-md border border-border object-cover" />
                            <p class="mt-1.5 text-xs text-muted-foreground">
                                Déposée le {{ datetime(delivery.proof_at) }} — en attente de confirmation du client.
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-3">
                            <input type="file" accept="image/*" @change="pickFile"
                                   class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-foreground" />
                            <img v-if="preview" :src="preview" alt="Aperçu" class="max-h-36 rounded-md border border-border object-cover" />
                            <p v-if="form.errors.proof" class="text-xs text-destructive">{{ form.errors.proof }}</p>

                            <Button type="submit" size="sm" :disabled="!form.proof || form.processing">
                                <Upload class="mr-2 h-4 w-4" />
                                {{ delivery.has_proof ? 'Remplacer la photo' : 'Attester la livraison' }}
                            </Button>
                            <p class="text-xs text-muted-foreground">
                                Sans cette photo, le client ne peut pas confirmer la réception et votre paiement reste bloqué.
                            </p>
                        </form>
                    </template>

                    <p v-else class="text-sm text-muted-foreground">
                        En attente du paiement du client.
                    </p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-border bg-card">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Vos articles</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Qté</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Prix</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="product in order.products" :key="product.id">
                            <td class="px-4 py-3">{{ product.name }}</td>
                            <td class="px-4 py-3 text-right">{{ product.pivot?.quantity ?? 1 }}</td>
                            <td class="px-4 py-3 text-right">{{ money(product.pivot?.price ?? product.price) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Link :href="route('backoffice.supplier.orders.index')" class="inline-block text-sm text-primary hover:underline">
                ← Toutes les commandes
            </Link>
        </div>
    </SupplierLayout>
</template>
