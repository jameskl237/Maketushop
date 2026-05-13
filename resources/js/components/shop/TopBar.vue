<script setup>
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Button } from '@/components/ui/button';
import { useCartStore } from '@/stores/cart';
import { Link, usePage } from '@inertiajs/vue3';
import { ShoppingCart, Store } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps({
    showBackHome: { type: Boolean, default: false },
});

const page = usePage();
const cartStore = useCartStore();
const cartItemsCount = computed(() => cartStore.itemsCount);
const isAuthenticated = computed(() => !!page.props.auth?.user);
</script>

<template>
    <header class="sticky top-0 z-50 h-14 border-b border-white/10 bg-white/65 backdrop-blur-2xl backdrop-saturate-150 dark:bg-[#130D22]/70 dark:border-white/5" style="view-transition-name: topbar">
        <div class="mx-auto flex h-full max-w-7xl items-center justify-between px-4">
            <Link :href="showBackHome ? '/' : route('products.index')" class="group flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-2xl bg-primary text-white shadow-sm transition-transform group-active:scale-95">
                    <span class="font-display text-sm font-black">M</span>
                </div>
                <span class="font-display text-[17px] font-extrabold tracking-tight text-foreground">
                    Maketu<span class="text-primary">Shop</span>
                </span>
            </Link>

            <div class="flex items-center gap-2">
                <Link
                    :href="route('register')"
                    class="flex items-center gap-1.5 h-8 rounded-xl bg-primary/10 px-3 text-[11px] font-bold text-primary transition hover:bg-primary/20 active:scale-95"
                >
                    <Store class="h-3.5 w-3.5" />
                    Je veux vendre
                </Link>

                <LanguageSwitcher />

                <ThemeToggle :floating="false" class="h-9 w-9 rounded-2xl bg-glass border border-glass-border backdrop-blur-xl shadow-none" />

                <Link :href="route('cart.index')" class="relative">
                    <Button
                        type="button"
                        variant="default"
                        size="icon"
                        class="h-9 w-9 rounded-2xl"
                    >
                        <ShoppingCart class="h-4 w-4" />
                    </Button>
                    <span
                        v-if="cartItemsCount"
                        class="absolute -right-1 -top-1 flex h-4.5 min-w-[18px] items-center justify-center rounded-full border-2 border-white bg-orange px-1 text-[9px] font-black leading-none text-white shadow-lg dark:border-[#130D22]"
                    >
                        {{ cartItemsCount }}
                    </span>
                </Link>
            </div>
        </div>
    </header>
</template>
