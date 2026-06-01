<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    CheckCircle,
    FileText,
    Heart,
    Package,
    ShoppingBag,
    Sparkles,
    User as UserIcon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    orders: { type: Array, default: () => [] },
    favorites: { type: Array, default: () => [] },
    quoteRequests: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const tabs = [
    { key: 'orders', label: 'Commandes', icon: ShoppingBag },
    { key: 'favorites', label: 'Favoris', icon: Heart },
    { key: 'quotes', label: 'Devis', icon: FileText },
    { key: 'profile', label: 'Profil', icon: UserIcon },
];
const activeTab = ref('orders');

const formatDate = (dateString) =>
    new Date(dateString).toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' });

const formatPrice = (price) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 }).format(price ?? 0);

const statusBadgeClass = (status) =>
    status === 'delivered'
        ? 'bg-green-500/10 text-green-600 border-green-500/20'
        : 'bg-amber-500/10 text-amber-600 border-amber-500/20';

const markAsDelivered = (order) => {
    router.patch(route('orders.delivered', { order: order.id }), {}, { preserveScroll: true });
};

const favoriteLink = (fav) =>
    fav.type === 'service'
        ? route('services.show', { service: fav.id })
        : route('products.show', { product: fav.id });
</script>

<template>
    <Head title="Mon compte" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-foreground">Mon compte</h2>
                    <p class="text-muted-foreground">Bonjour {{ user.name }}, gérez vos commandes, favoris et devis.</p>
                </div>
                <Link :href="route('products.index')" class="hidden sm:block">
                    <Button>
                        <ShoppingBag class="mr-2 h-4 w-4" />
                        Boutique
                    </Button>
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-2xl border border-border bg-primary/5 p-4">
                        <ShoppingBag class="h-5 w-5 text-primary" />
                        <p class="mt-2 text-2xl font-extrabold">{{ orders.length }}</p>
                        <p class="text-[11px] text-muted-foreground">Commandes</p>
                    </div>
                    <div class="rounded-2xl border border-border bg-orange/5 p-4">
                        <Heart class="h-5 w-5 text-orange" />
                        <p class="mt-2 text-2xl font-extrabold">{{ favorites.length }}</p>
                        <p class="text-[11px] text-muted-foreground">Favoris</p>
                    </div>
                    <div class="rounded-2xl border border-border bg-primary/5 p-4">
                        <FileText class="h-5 w-5 text-primary" />
                        <p class="mt-2 text-2xl font-extrabold">{{ quoteRequests.length }}</p>
                        <p class="text-[11px] text-muted-foreground">Devis</p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex gap-1 overflow-x-auto rounded-2xl border border-border bg-card p-1">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="flex flex-1 items-center justify-center gap-1.5 whitespace-nowrap rounded-xl px-3 py-2 text-[12px] font-semibold transition-colors"
                        :class="activeTab === tab.key ? 'bg-primary text-white' : 'text-muted-foreground hover:bg-accent'"
                        @click="activeTab = tab.key"
                    >
                        <component :is="tab.icon" class="h-4 w-4" />
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Onglet Commandes -->
                <div v-if="activeTab === 'orders'" class="space-y-3">
                    <div v-if="orders.length === 0" class="rounded-2xl border-2 border-dashed border-border py-12 text-center">
                        <ShoppingBag class="mx-auto mb-3 h-10 w-10 text-muted-foreground opacity-20" />
                        <p class="text-muted-foreground">Aucune commande pour l'instant.</p>
                        <Link :href="route('products.index')" class="mt-3 inline-block">
                            <Button variant="outline">Découvrir la boutique</Button>
                        </Link>
                    </div>
                    <div
                        v-for="order in orders"
                        :key="order.id"
                        class="flex flex-col gap-3 rounded-2xl border border-border p-4 md:flex-row md:items-center md:justify-between"
                    >
                        <div class="flex items-start gap-3">
                            <div class="rounded-full bg-primary/10 p-2 text-primary">
                                <ShoppingBag class="h-5 w-5" />
                            </div>
                            <div class="space-y-1">
                                <p class="font-bold text-foreground">{{ order.order_number }}</p>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] text-muted-foreground">
                                    <span class="flex items-center gap-1"><Calendar class="h-3 w-3" />{{ formatDate(order.created_at) }}</span>
                                    <span class="flex items-center gap-1"><Package class="h-3 w-3" />{{ order.total_products }} article(s)</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t pt-3 md:border-t-0 md:pt-0">
                            <div class="text-right">
                                <p class="text-lg font-bold text-foreground">{{ formatPrice(order.total_price) }}</p>
                                <Badge variant="outline" :class="statusBadgeClass(order.status)">
                                    {{ order.status === 'delivered' ? 'Livrée' : 'En attente' }}
                                </Badge>
                            </div>
                            <Button
                                v-if="order.status === 'pending'"
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="markAsDelivered(order)"
                            >
                                <CheckCircle class="mr-1 h-4 w-4" />
                                Reçue
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Onglet Favoris -->
                <div v-else-if="activeTab === 'favorites'" class="space-y-3">
                    <div v-if="favorites.length === 0" class="rounded-2xl border-2 border-dashed border-border py-12 text-center">
                        <Heart class="mx-auto mb-3 h-10 w-10 text-muted-foreground opacity-20" />
                        <p class="text-muted-foreground">Aucun favori pour l'instant.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <Link
                            v-for="fav in favorites"
                            :key="`${fav.type}-${fav.id}`"
                            :href="favoriteLink(fav)"
                            class="overflow-hidden rounded-2xl border border-border bg-card transition-transform active:scale-95"
                        >
                            <div class="aspect-square overflow-hidden bg-gradient-to-br from-[#FFE9EE] to-[#FFF1E6]">
                                <img :src="fav.image || '/images/Maketu1.png'" :alt="fav.name" class="h-full w-full object-cover" loading="lazy" />
                            </div>
                            <div class="space-y-1 p-2.5">
                                <Badge v-if="fav.type === 'service'" class="bg-orange/90 text-[9px] text-white">
                                    <Sparkles class="mr-0.5 h-2.5 w-2.5" /> Service
                                </Badge>
                                <p class="line-clamp-1 text-[12px] font-bold text-foreground">{{ fav.name }}</p>
                                <p class="text-[11px] text-muted-foreground">{{ fav.shop }}</p>
                                <p class="text-[12px] font-extrabold text-primary">
                                    {{ fav.price == null ? 'Sur devis' : formatPrice(fav.price) }}
                                </p>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Onglet Devis -->
                <div v-else-if="activeTab === 'quotes'" class="space-y-3">
                    <div v-if="quoteRequests.length === 0" class="rounded-2xl border-2 border-dashed border-border py-12 text-center">
                        <FileText class="mx-auto mb-3 h-10 w-10 text-muted-foreground opacity-20" />
                        <p class="text-muted-foreground">Vous n'avez envoyé aucune demande de devis.</p>
                    </div>
                    <div
                        v-for="quote in quoteRequests"
                        :key="quote.id"
                        class="rounded-2xl border border-border p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <Link :href="route('services.show', { service: quote.service_id })" class="font-semibold text-foreground hover:text-primary">
                                    {{ quote.service_title || 'Service' }}
                                </Link>
                                <p v-if="quote.budget" class="mt-1 text-[12px] text-muted-foreground">Budget : {{ quote.budget }}</p>
                                <p v-if="quote.message" class="mt-1 line-clamp-2 text-[12px] text-muted-foreground">{{ quote.message }}</p>
                            </div>
                            <Badge variant="outline" :class="quote.status === 'traite' ? statusBadgeClass('delivered') : statusBadgeClass('pending')">
                                {{ quote.status === 'traite' ? 'Traité' : 'En attente' }}
                            </Badge>
                        </div>
                        <p class="mt-2 text-[11px] text-muted-foreground">{{ formatDate(quote.created_at) }}</p>
                    </div>
                </div>

                <!-- Onglet Profil -->
                <div v-else-if="activeTab === 'profile'" class="space-y-3">
                    <div class="rounded-2xl border border-border p-5">
                        <div class="flex items-center gap-4">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/15 text-xl font-bold text-primary">
                                {{ user.name?.charAt(0) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate font-bold text-foreground">{{ user.name }}</p>
                                <p class="truncate text-[12px] text-muted-foreground">{{ user.email }}</p>
                                <p v-if="user.phone" class="text-[12px] text-muted-foreground">{{ user.phone }}</p>
                            </div>
                        </div>
                        <Link :href="route('profile.edit')" class="mt-4 block">
                            <Button variant="outline" class="w-full">Modifier mon profil</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
