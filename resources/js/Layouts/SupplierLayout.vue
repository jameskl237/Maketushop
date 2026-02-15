<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SupplierSidebar from '@/components/supplier/SupplierSidebar.vue';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, Menu, Package, ShoppingCart, Store } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    activeRoute: {
        type: String,
        default: 'backoffice.supplier.dashboard',
    },
    canCreateShop: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['create-shop']);
const mobileSidebarVisible = ref(false);

const openCreateShop = () => emit('create-shop');
const toggleMobileSidebar = () => {
    mobileSidebarVisible.value = !mobileSidebarVisible.value;
};
</script>

<template>
    <!-- Exemple:
      <SupplierLayout title="Espace Fournisseur" @create-shop="openDialog = true">
        <template #content>...</template>
      </SupplierLayout>
    -->
    <AuthenticatedLayout :show-logo="false">
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-foreground sm:text-2xl">{{ title }}</h2>
                    <p v-if="subtitle" class="mt-1 text-sm text-muted-foreground">{{ subtitle }}</p>
                </div>
                <!-- <Button
                    variant="outline"
                    size="icon"
                    class="relative z-10 md:hidden"
                    aria-label="Afficher ou masquer la sidebar fournisseur"
                    :aria-expanded="mobileSidebarVisible"
                    @click="toggleMobileSidebar"
                >
                    <Menu class="h-4 w-4" />
                </Button> -->
            </div>
        </template>

        <div class="overflow-x-hidden py-6 sm:py-8">
            <div class="mx-auto max-w-7xl overflow-x-hidden px-4 sm:px-6 lg:px-8">
                <div class="mb-4 md:hidden">
                    <div class="rounded-xl border border-border bg-card p-3 shadow-sm">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded-lg px-2 py-2 text-left text-sm font-medium text-foreground"
                            :aria-expanded="mobileSidebarVisible"
                            aria-controls="supplier-mobile-sidebar"
                            @click="toggleMobileSidebar"
                        >
                            <span>Menu fournisseur</span>
                            <ChevronUp v-if="mobileSidebarVisible" class="h-4 w-4" />
                            <ChevronDown v-else class="h-4 w-4" />
                        </button>

                        <div
                            v-if="mobileSidebarVisible"
                            id="supplier-mobile-sidebar"
                            class="mt-2 space-y-1 border-t border-border pt-3"
                        >
                            <Link
                                :href="route('backoffice.supplier.dashboard')"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-all duration-300"
                                :class="activeRoute === 'backoffice.supplier.dashboard'
                                    ? 'bg-primary/12 text-primary font-medium'
                                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                                @click="mobileSidebarVisible = false"
                            >
                                <Store class="h-4 w-4" />
                                Dashboard
                            </Link>
                            <Link
                                :href="route('backoffice.supplier.shops.index')"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-all duration-300"
                                :class="activeRoute === 'backoffice.supplier.shops.index'
                                    ? 'bg-primary/12 text-primary font-medium'
                                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                                @click="mobileSidebarVisible = false"
                            >
                                <Store class="h-4 w-4" />
                                Boutiques
                            </Link>
                            <Link
                                :href="route('backoffice.supplier.orders.index')"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-all duration-300"
                                :class="activeRoute === 'backoffice.supplier.orders.index'
                                    ? 'bg-primary/12 text-primary font-medium'
                                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                                @click="mobileSidebarVisible = false"
                            >
                                <ShoppingCart class="h-4 w-4" />
                                Commandes
                            </Link>
                            <Link
                                :href="route('backoffice.supplier.products.index')"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-all duration-300"
                                :class="activeRoute === 'backoffice.supplier.products.index'
                                    ? 'bg-primary/12 text-primary font-medium'
                                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                                @click="mobileSidebarVisible = false"
                            >
                                <Package class="h-4 w-4" />
                                Produits
                            </Link>
                            <Button
                                v-if="canCreateShop"
                                class="mt-2 w-full"
                                @click="
                                    mobileSidebarVisible = false;
                                    openCreateShop();
                                "
                            >
                                Creer ma boutique
                            </Button>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
                    <aside class="hidden lg:block">
                        <div class="sticky top-6">
                            <SupplierSidebar
                                :active-route="activeRoute"
                                :can-create-shop="canCreateShop"
                                @create-shop="openCreateShop"
                            />
                        </div>
                    </aside>

                    <main class="min-w-0 space-y-6 overflow-x-hidden">
                        <slot name="content" />
                    </main>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

