<script setup>
import { Link } from '@inertiajs/vue3';
import { Home, Users, Store, Package, Tag, CreditCard, Settings } from 'lucide-vue-next';

const props = defineProps({
    activeRoute: {
        type: String,
        default: 'backoffice.admin.dashboard',
    },
});
</script>

<template>
    <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
        <nav class="space-y-1">
            <Link :href="route('backoffice.admin.dashboard')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm"
                :class="activeRoute === 'backoffice.admin.dashboard' ? 'bg-primary/12 text-primary font-medium' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                <Home class="h-4 w-4" />
                Tableau de bord
            </Link>

            <div class="pt-3">
                <div class="text-xs font-semibold uppercase text-muted-foreground px-3">Gestion</div>
                <div class="mt-2 space-y-1">
                    <Link :href="route('backoffice.admin.management')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm"
                        :class="activeRoute === 'backoffice.admin.management' ? 'bg-primary/12 text-primary font-medium' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <Users class="h-4 w-4" />
                        Gestion (Tous)
                    </Link>

                    <Link :href="route('backoffice.admin.shops.index')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm"
                        :class="activeRoute === 'backoffice.admin.shops.index' ? 'bg-primary/12 text-primary font-medium' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <Store class="h-4 w-4" />
                        Boutiques
                    </Link>

                    <Link :href="route('backoffice.admin.products.index')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm"
                        :class="activeRoute === 'backoffice.admin.products.index' ? 'bg-primary/12 text-primary font-medium' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <Package class="h-4 w-4" />
                        Produits
                    </Link>

                    <Link :href="route('backoffice.admin.categories.index')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm"
                        :class="activeRoute === 'backoffice.admin.categories.index' ? 'bg-primary/12 text-primary font-medium' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <Tag class="h-4 w-4" />
                        Catégories
                    </Link>
                </div>
            </div>

            <div class="pt-3">
                <div class="text-xs font-semibold uppercase text-muted-foreground px-3">Finances</div>
                <div class="mt-2">
                    <Link :href="route('backoffice.admin.dashboard')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-muted-foreground hover:bg-accent hover:text-accent-foreground">
                        <CreditCard class="h-4 w-4" />
                        Comptabilité
                    </Link>
                </div>
            </div>

            <div class="pt-3">
                <div class="text-xs font-semibold uppercase text-muted-foreground px-3">Paramètres</div>
                <div class="mt-2">
                    <Link :href="route('profile.edit')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-muted-foreground hover:bg-accent hover:text-accent-foreground">
                        <Settings class="h-4 w-4" />
                        Paramètres
                    </Link>
                </div>
            </div>
        </nav>
    </div>
</template>
