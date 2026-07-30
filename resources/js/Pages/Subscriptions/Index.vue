<script setup>
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Check, Crown, X, ArrowRight } from 'lucide-vue-next';

const props = defineProps({
    plans: { type: Object, required: true },
    shops: { type: Array, default: () => [] },
});

const { t } = useI18n();
const page = usePage();
const authUser = computed(() => page.props.auth.user);

const formatPrice = (value) =>
    new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        maximumFractionDigits: 0,
    }).format(value);

const isFreePlan = (planKey) => planKey === 'free';

const subscribe = (shopId, plan) => {
    if (plan === 'free') {
        router.post(route('subscriptions.subscribe', { shop: shopId }), { plan });
        return;
    }
    router.visit(route('subscriptions.payment', { shop: shopId }));
};

const cancelSubscription = (shopId) => {
    if (confirm('Annuler votre abonnement ? Vous passerez au plan Gratuit.')) {
        router.post(route('subscriptions.cancel', { shop: shopId }));
    }
};

const hasActiveStandard = (shop) =>
    shop.active_subscription?.plan === 'standard' && shop.active_subscription?.is_active;
</script>

<template>
    <Head title="Abonnements Boutique" />

    <div class="min-h-screen bg-background">
        <ProductsNavbar />

        <main class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
            <div class="text-center space-y-2">
                <h1 class="text-3xl font-bold">Abonnements Boutique</h1>
                <p class="text-muted-foreground max-w-xl mx-auto">
                    Choisissez le plan adapté à votre activité. Le plan Standard débloque toutes les fonctionnalités.
                </p>
            </div>

            <!-- Plans -->
            <div class="grid gap-6 md:grid-cols-2 max-w-2xl mx-auto">
                <Card
                    v-for="(plan, key) in plans"
                    :key="key"
                    class="relative"
                    :class="plan.key === 'standard' ? 'border-primary shadow-lg' : ''"
                >
                    <Badge
                        v-if="plan.key === 'standard'"
                        class="absolute -top-3 left-1/2 -translate-x-1/2 bg-primary text-primary-foreground"
                    >
                        Recommandé
                    </Badge>
                    <CardHeader>
                        <CardTitle class="text-xl">{{ plan.name }}</CardTitle>
                        <CardDescription v-if="plan.key === 'free'">Pour démarrer</CardDescription>
                        <CardDescription v-else>Pour les pros</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="text-3xl font-black">
                            {{ formatPrice(plan.price) }}
                            <span class="text-base font-normal text-muted-foreground">/ mois</span>
                        </div>
                        <ul class="space-y-2 text-sm">
                            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2">
                                <Check class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                <span>{{ feature }}</span>
                            </li>
                        </ul>
                    </CardContent>
                    <CardFooter>
                        <Button
                            class="w-full"
                            :variant="plan.key === 'standard' ? 'default' : 'outline'"
                            disabled
                        >
                            {{ plan.key === 'free' ? 'Gratuit' : formatPrice(plan.price) }}
                        </Button>
                    </CardFooter>
                </Card>
            </div>

            <!-- Vos boutiques -->
            <section v-if="shops.length" class="space-y-4">
                <h2 class="text-2xl font-bold">Mes boutiques</h2>
                <div class="space-y-4">
                    <Card v-for="shop in shops" :key="shop.id">
                        <CardContent class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pt-6">
                            <div class="space-y-1">
                                <h3 class="font-bold text-lg">{{ shop.name }}</h3>
                                <p class="text-sm text-muted-foreground">
                                    <template v-if="shop.active_subscription">
                                        Plan :
                                        <Badge variant="secondary">
                                            {{ shop.active_subscription.plan === 'standard' ? 'Standard' : 'Gratuit' }}
                                        </Badge>
                                        <span v-if="shop.active_subscription.is_active" class="ml-2 text-green-600 text-xs">
                                            (Actif jusqu'au {{ new Date(shop.active_subscription.ends_at).toLocaleDateString('fr-FR') }})
                                        </span>
                                        <span v-else class="ml-2 text-red-500 text-xs">(Expiré)</span>
                                    </template>
                                    <template v-else>
                                        <Badge variant="outline">Aucun abonnement</Badge>
                                    </template>
                                </p>
                            </div>
                            <div class="flex gap-2 shrink-0">
                                <Button
                                    v-if="hasActiveStandard(shop)"
                                    variant="outline"
                                    size="sm"
                                    @click="cancelSubscription(shop.id)"
                                >
                                    <X class="h-4 w-4 mr-1" />
                                    Résilier
                                </Button>
                                <Button
                                    v-else
                                    size="sm"
                                    @click="subscribe(shop.id, 'standard')"
                                >
                                    <Crown class="h-4 w-4 mr-1" />
                                    Passer à Standard (1000 FCFA)
                                </Button>
                                <Button
                                    v-if="!shop.active_subscription || !shop.active_subscription.is_active"
                                    variant="ghost"
                                    size="sm"
                                    @click="subscribe(shop.id, 'free')"
                                >
                                    Gratuit
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <section v-else class="text-center py-12 text-muted-foreground">
                <p>Vous n'avez pas encore de boutique. Créez-en une depuis votre tableau de bord fournisseur.</p>
                <Button class="mt-4" as="a" :href="route('backoffice.supplier.shops.index')">
                    Gérer mes boutiques
                    <ArrowRight class="h-4 w-4 ml-2" />
                </Button>
            </section>
        </main>
    </div>
</template>
