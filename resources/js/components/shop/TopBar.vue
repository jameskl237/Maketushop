<script setup>
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Button } from '@/components/ui/button';
import { getI18nLocale, setI18nLocale } from '@/i18n';
import { useCartStore } from '@/stores/cart';
import { Link } from '@inertiajs/vue3';
import { Languages, ShoppingCart } from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineProps({
    showBackHome: { type: Boolean, default: false },
});

const cartStore = useCartStore();
const locale = ref(getI18nLocale());

const cartItemsCount = computed(() => cartStore.itemsCount);

const toggleLocale = () => {
    const nextLocale = locale.value === 'fr' ? 'en' : 'fr';
    setI18nLocale(nextLocale);
    locale.value = nextLocale;
};
</script>

<template>
    <header class="sticky top-0 z-50 h-14 border-b border-white/5 bg-white/60 backdrop-blur-xl dark:bg-[#130D22]/60">
        <div class="mx-auto flex h-full max-w-7xl items-center justify-between px-4">
            <Link :href="showBackHome ? '/' : route('products.index')" class="group flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-pink text-white shadow-lg shadow-primary/20 transition-transform group-active:scale-95">
                    <span class="font-display text-sm font-black">M</span>
                </div>
                <span class="font-display text-[17px] font-extrabold tracking-tight text-foreground">
                    Maketu<span class="bg-gradient-to-r from-primary to-pink bg-clip-text text-transparent">Shop</span>
                </span>
            </Link>

            <div class="flex items-center gap-2">
                <Button
                    type="button"
                    variant="glass"
                    size="icon"
                    class="h-9 w-9 rounded-xl shadow-none"
                    @click="toggleLocale"
                >
                    <Languages class="h-4 w-4" />
                </Button>

                <ThemeToggle :floating="false" class="h-9 w-9 rounded-xl bg-glass border border-glass-border backdrop-blur-xl shadow-none" />

                <Link :href="route('cart.index')" class="relative">
                    <Button
                        type="button"
                        variant="mesh"
                        size="icon"
                        class="h-9 w-9 rounded-xl"
                    >
                        <ShoppingCart class="h-4 w-4" />
                    </Button>
                    <span
                        v-if="cartItemsCount"
                        class="absolute -right-1 -top-1 flex h-4.5 min-w-[18px] items-center justify-center rounded-full border-2 border-white bg-pink px-1 text-[9px] font-black leading-none text-white shadow-lg dark:border-[#130D22]"
                    >
                        {{ cartItemsCount }}
                    </span>
                </Link>
            </div>
        </div>
    </header>
</template>
