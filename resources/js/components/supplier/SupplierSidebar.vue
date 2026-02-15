<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart3, Package, ShoppingCart, Sparkles, Store } from 'lucide-vue-next';

defineProps({
    activeRoute: {
        type: String,
        default: 'backoffice.supplier.dashboard',
    },
    canCreateShop: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['navigate', 'create-shop']);
const page = usePage();

const navigationItems = [
    { key: 'dashboard', label: 'Tableau de bord', icon: BarChart3, route: 'backoffice.supplier.dashboard', disabled: false },
    { key: 'shops', label: 'Mes boutiques', icon: Store, route: 'backoffice.supplier.shops.index', disabled: false },
    { key: 'orders', label: 'Commandes', icon: ShoppingCart, route: 'backoffice.supplier.orders.index', disabled: false },
    { key: 'products', label: 'Produits', icon: Package, route: 'backoffice.supplier.products.index', disabled: false },
];

const onNavigate = () => emit('navigate');
</script>

<template>
    <!-- Exemple: <SupplierSidebar @create-shop="openDialog = true" /> -->
    <div class="space-y-4">
        <Card class="border-border/60">
            <CardContent class="p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/15 text-primary">
                        {{ page.props.auth.user.name.charAt(0) }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-foreground">{{ page.props.auth.user.name }}</p>
                        <Badge variant="secondary" class="mt-1">
                            <Sparkles class="mr-1 h-3 w-3" />
                            Supplier
                        </Badge>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card class="border-border/60">
            <CardContent class="p-3">
                <nav class="space-y-1" aria-label="Navigation fournisseur">
                    <template v-for="item in navigationItems" :key="item.key">
                        <Link
                            v-if="item.route"
                            :href="route(item.route)"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-all duration-300"
                            :class="activeRoute === item.route
                                ? 'bg-primary/12 text-primary font-medium'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                            @click="onNavigate"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            <span>{{ item.label }}</span>
                        </Link>

                        <button
                            v-else
                            type="button"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-muted-foreground opacity-70 transition-all duration-300"
                            aria-disabled="true"
                            disabled
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            <span>{{ item.label }}</span>
                        </button>
                    </template>
                </nav>
            </CardContent>
        </Card>

        <Button v-if="canCreateShop" class="w-full" @click="$emit('create-shop')">
            Creer ma boutique
        </Button>
    </div>
</template>

