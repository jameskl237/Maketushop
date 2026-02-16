<script setup>
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Button } from '@/components/ui/button';
import { useCartStore } from '@/stores/cart';
import { Link } from '@inertiajs/vue3';
import { ShoppingCart, Store } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const cartStore = useCartStore();
const cartDropdownOpen = ref(false);
const cartDropdownRef = ref(null);

const cartItems = computed(() => cartStore.items);
const cartItemsCount = computed(() => cartStore.itemsCount);

const onDocumentClick = (event) => {
    if (!cartDropdownOpen.value) return;
    if (cartDropdownRef.value?.contains(event.target)) return;
    cartDropdownOpen.value = false;
};

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
});
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-border bg-background/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                <Link :href="route('products.index')" class="text-base font-semibold">MaketuShop</Link>
                <nav class="hidden items-center gap-3 sm:flex">
                    <Link :href="route('products.index')" class="text-sm text-muted-foreground transition hover:text-foreground">
                        Produits
                    </Link>
                    <Link :href="route('shops.index')" class="text-sm text-muted-foreground transition hover:text-foreground">
                        Boutiques
                    </Link>
                </nav>
            </div>

            <div class="flex items-center gap-2">
                <LanguageSwitcher :floating="false" />
                <ThemeToggle :floating="false" />
                <Link :href="route('shops.index')">
                    <Button type="button" variant="outline" size="icon" aria-label="Voir les boutiques">
                        <Store class="h-4 w-4" />
                    </Button>
                </Link>

                <div ref="cartDropdownRef" class="relative">
                    <Button
                        type="button"
                        variant="outline"
                        class="gap-2"
                        :aria-expanded="cartDropdownOpen"
                        aria-haspopup="menu"
                        @click.stop="cartDropdownOpen = !cartDropdownOpen"
                    >
                        <ShoppingCart class="h-4 w-4" />
                        <span class="hidden sm:inline">Panier</span>
                        <span class="rounded-full bg-primary px-1.5 text-xs text-primary-foreground">{{ cartItemsCount }}</span>
                    </Button>

                    <div
                        v-if="cartDropdownOpen"
                        class="absolute right-0 top-12 z-50 w-72 rounded-lg border border-border bg-background p-3 shadow-lg"
                    >
                        <p class="mb-2 text-sm font-semibold">Produits ajoutés</p>

                        <div v-if="!cartItems.length" class="rounded-md border border-dashed border-border p-3 text-xs text-muted-foreground">
                            Votre panier est vide.
                        </div>

                        <ul v-else class="max-h-48 space-y-2 overflow-y-auto">
                            <li
                                v-for="item in cartItems"
                                :key="item.id"
                                class="flex items-center justify-between rounded-md border border-border px-2 py-1.5 text-xs"
                            >
                                <span class="line-clamp-1">{{ item.name }}</span>
                                <span class="text-muted-foreground">x{{ item.quantity }}</span>
                            </li>
                        </ul>

                        <Link :href="route('cart.index')" @click="cartDropdownOpen = false">
                            <Button class="mt-3 w-full">Voir le panier</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

