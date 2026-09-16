<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, useForm } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
import { money, date } from '@/lib/orders';

defineProps({
    plans: Object,
    shops: Array,
});

const form = useForm({});

const subscribe = (shopId, plan) =>
    form.post(
        plan === 'free'
            ? route('backoffice.supplier.subscriptions.free', shopId)
            : route('backoffice.supplier.subscriptions.standard', shopId),
        { preserveScroll: true },
    );

const cancel = (shopId) =>
    form.post(route('backoffice.supplier.subscriptions.cancel', shopId), { preserveScroll: true });
</script>

<template>
    <Head title="Abonnements" />

    <SupplierLayout>
        <div class="mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
            <h1 class="text-xl font-semibold">Abonnements</h1>

            <div v-if="!shops.length" class="rounded-lg border border-border bg-card p-10 text-center text-sm text-muted-foreground">
                Créez d'abord une boutique pour souscrire un abonnement.
            </div>

            <div v-for="shop in shops" :key="shop.id" class="rounded-lg border border-border bg-card p-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-medium">{{ shop.name }}</h2>
                        <p v-if="shop.active_subscription" class="text-xs text-muted-foreground">
                            Actif jusqu'au {{ date(shop.active_subscription.ends_at) }}
                        </p>
                        <p v-else class="text-xs text-muted-foreground">Aucun abonnement actif</p>
                    </div>
                    <Badge v-if="shop.active_subscription" variant="default">
                        {{ plans[shop.active_subscription.plan]?.name ?? shop.active_subscription.plan }}
                    </Badge>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div v-for="plan in plans" :key="plan.key"
                         class="rounded-lg border p-4"
                         :class="shop.active_subscription?.plan === plan.key ? 'border-primary bg-primary/5' : 'border-border'">
                        <div class="flex items-baseline justify-between gap-2">
                            <h3 class="font-medium">{{ plan.name }}</h3>
                            <span class="text-lg font-semibold">{{ plan.price ? money(plan.price) : 'Gratuit' }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ plan.duration_days }} jours</p>

                        <ul class="mt-3 space-y-1">
                            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-1.5 text-sm">
                                <Check class="mt-0.5 h-3.5 w-3.5 shrink-0 text-primary" />
                                <span>{{ feature }}</span>
                            </li>
                        </ul>

                        <Button v-if="shop.active_subscription?.plan !== plan.key"
                                class="mt-4 w-full" size="sm" :disabled="form.processing"
                                @click="subscribe(shop.id, plan.key)">
                            {{ plan.price ? 'Souscrire' : 'Activer' }}
                        </Button>
                        <Button v-else class="mt-4 w-full" size="sm" variant="outline"
                                :disabled="form.processing" @click="cancel(shop.id)">
                            Résilier
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </SupplierLayout>
</template>
