<script setup>
import StoreFooter from '@/components/shop/StoreFooter.vue';
import { useCartStore } from '@/stores/cart';
import { Link, usePage } from '@inertiajs/vue3';
import { Briefcase, LogIn, ShoppingBag, ShoppingCart, Store, User as UserIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const page = usePage();
const cartStore = useCartStore();
const cartCount = computed(() => cartStore.itemsCount);

const isAuthenticated = computed(() => !!page.props.auth?.user);

const isActive = (href) => {
    if (!href) return false;
    if (href === '/') return page.url === '/';
    return page.url === href || page.url.startsWith(href + '/') || page.url.startsWith(href + '?');
};

// Onglet Compte dynamique : "Connexion" si invité, "Mon compte" si connecté.
// (L'accueil reste accessible via le logo MaketuShop dans le TopBar.)
const accountTab = computed(() =>
    isAuthenticated.value
        ? { label: t('shopChrome.tabs.account'), href: route('dashboard'), icon: UserIcon }
        : { label: t('shopChrome.tabs.login'), href: route('login'), icon: LogIn },
);

const tabs = computed(() => [
    accountTab.value,
    { label: t('shopChrome.tabs.shops'),    href: route('shops.index'),     icon: Store },
    { label: t('shopChrome.tabs.products'), href: route('products.index'),  icon: ShoppingBag, primary: true },
    { label: t('shopChrome.tabs.cart'),     href: route('cart.index'),      icon: ShoppingCart, badge: cartCount.value },
    { label: t('shopChrome.tabs.services'), href: route('services.index'),  icon: Briefcase },
]);
</script>

<template>
    <!-- Pied de page desktop (les pages boutique terminent toutes par BottomNav). -->
    <StoreFooter />

    <!-- Spacer so content isn't hidden behind the bar -->
    <div class="h-24 lg:hidden" />

    <!-- Navigation mobile uniquement : sur desktop, l'en-tête marketplace prend le relais. -->
    <div class="fixed bottom-4 left-1/2 z-50 w-[calc(100%-32px)] max-w-sm -translate-x-1/2 lg:hidden" style="view-transition-name: bottomnav">
        <nav class="relative flex h-16 items-end justify-around rounded-[26px] border border-border bg-card/95 px-3 pb-2 pt-3 shadow-[0_8px_32px_rgba(0,0,0,0.14)] backdrop-blur-2xl dark:shadow-[0_8px_32px_rgba(0,0,0,0.55)]">

            <template v-for="tab in tabs" :key="tab.label">

                <!-- FAB central -->
                <template v-if="tab.primary">
                    <Link
                        :href="tab.href"
                        class="relative -top-5 flex flex-col items-center gap-1"
                    >
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-[22px] bg-primary shadow-[0_6px_20px_rgb(var(--primary-rgb)/0.45)] transition-transform active:scale-90"
                            :class="isActive(tab.href) ? 'ring-2 ring-white/20' : ''"
                        >
                            <component :is="tab.icon" class="h-6 w-6 text-white" />
                        </div>
                        <span class="text-[11px] font-semibold uppercase tracking-wide"
                            :class="isActive(tab.href) ? 'text-foreground' : 'text-shop-light'">
                            {{ tab.label }}
                        </span>
                    </Link>
                </template>

                <!-- Tab normal -->
                <template v-else>
                    <Link
                        :href="tab.href"
                        class="relative flex flex-col items-center gap-1 transition-transform active:scale-90"
                        :class="isActive(tab.href) ? 'text-foreground' : 'text-shop-light'"
                    >
                        <div class="relative">
                            <component :is="tab.icon" class="h-[22px] w-[22px]" />
                            <!-- Badge panier -->
                            <span
                                v-if="tab.badge"
                                class="absolute -right-2 -top-2 flex h-[16px] min-w-[16px] items-center justify-center rounded-full bg-orange px-1 text-[8px] font-black leading-none text-white"
                            >{{ tab.badge }}</span>
                        </div>
                        <span class="text-[11px] font-semibold uppercase tracking-wide">{{ tab.label }}</span>

                        <!-- Point actif -->
                        <div
                            v-if="isActive(tab.href)"
                            class="absolute bottom-0.5 h-1 w-1 rounded-full bg-primary"
                        />
                    </Link>
                </template>

            </template>
        </nav>
    </div>
</template>
