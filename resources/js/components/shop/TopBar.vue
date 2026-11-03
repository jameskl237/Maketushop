<script setup>
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { useCartStore } from '@/stores/cart';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, LayoutDashboard, MapPin, Menu, Package, Search, ShoppingCart, Store } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

defineProps({
    showBackHome: { type: Boolean, default: false },
});

const { t } = useI18n();
const page = usePage();
const cartStore = useCartStore();
const cartItemsCount = computed(() => cartStore.itemsCount);
const user = computed(() => page.props.auth?.user ?? null);
const categories = computed(() => page.props.nav_categories ?? []);
const isBackofficeUser = computed(() => ['admin', 'superadmin', 'supplier'].includes(user.value?.role));

// Pré-remplit la recherche avec la requête en cours (page résultats).
const currentParams = computed(() => new URLSearchParams(page.url.split('?')[1] || ''));
const query = ref(currentParams.value.get('search') || '');
const category = ref(currentParams.value.get('categories[0]') || '');

const submitSearch = () => {
    router.get(route('products.index'), {
        search: query.value.trim() || undefined,
        categories: category.value ? [Number(category.value)] : undefined,
    });
};

const firstName = computed(() => (user.value?.name || '').split(' ')[0]);
const quickCategories = computed(() => categories.value.slice(0, 7));
const isActive = (href) => page.url === href || page.url.startsWith(`${href}?`) || page.url.startsWith(`${href}/`);
const navLinks = computed(() => [
    { label: t('shopChrome.header.products'), href: '/products' },
    { label: t('shopChrome.header.shops'), href: '/shops' },
    { label: t('shopChrome.header.services'), href: '/services' },
]);
</script>

<template>
    <!-- ════════ Mobile / tablette ════════ -->
    <header
        class="sticky top-0 z-50 h-14 border-b border-border bg-card/90 backdrop-blur-xl lg:hidden"
        style="view-transition-name: topbar"
    >
        <div class="mx-auto flex h-full max-w-7xl items-center justify-between gap-2 px-3 sm:px-4">
            <Link href="/" class="group flex min-w-0 shrink items-center gap-1.5">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-primary text-white shadow-sm transition-transform group-active:scale-95">
                    <span class="font-display text-sm font-black">M</span>
                </div>
                <span class="truncate font-display text-[15px] font-extrabold tracking-tight text-foreground">
                    Maketu<span class="text-primary">Shop</span>
                </span>
            </Link>

            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <Link
                    :href="user ? route('dashboard') : route('register')"
                    class="flex h-9 shrink-0 items-center gap-1.5 rounded-xl bg-primary/10 px-2.5 text-xs font-bold text-primary transition hover:bg-primary/20 active:scale-95 sm:px-3"
                >
                    <Store class="h-4 w-4 shrink-0" />
                    <span class="hidden sm:inline">{{ user ? t('shopChrome.header.backoffice') : t('shopChrome.sellCta') }}</span>
                </Link>
                <LanguageSwitcher />
                <ThemeToggle :floating="false" class="h-9 w-9 shrink-0 rounded-xl" />
                <Link :href="route('cart.index')" class="relative hidden h-9 w-9 items-center justify-center rounded-xl bg-primary text-white sm:flex">
                    <ShoppingCart class="h-4 w-4" />
                    <span
                        v-if="cartItemsCount"
                        class="absolute -right-1.5 -top-1.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full border-2 border-card bg-orange px-1 text-[11px] font-black leading-none text-white"
                    >{{ cartItemsCount }}</span>
                </Link>
            </div>
        </div>
    </header>

    <!-- ════════ Desktop : en-tête marketplace ════════ -->
    <header class="sticky top-0 z-50 hidden text-white lg:block" style="view-transition-name: topbar">
        <!-- Rangée principale -->
        <div class="bg-header">
            <div class="mx-auto flex h-16 max-w-[1500px] items-center gap-4 px-6">
                <Link href="/" class="flex shrink-0 items-center gap-2 rounded-lg px-1 py-1 outline-none ring-white/60 focus-visible:ring-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary shadow-sm">
                        <span class="font-display text-base font-black">M</span>
                    </div>
                    <span class="font-display text-xl font-extrabold tracking-tight">
                        Maketu<span class="text-primary">Shop</span>
                    </span>
                </Link>

                <div class="hidden shrink-0 items-center gap-1.5 xl:flex">
                    <MapPin class="h-5 w-5 text-white/70" />
                    <div class="leading-tight">
                        <p class="text-[12px] text-white/60">{{ t('shopChrome.header.deliverTo') }}</p>
                        <p class="text-sm font-bold">{{ t('shopChrome.header.country') }}</p>
                    </div>
                </div>

                <!-- Recherche -->
                <form class="flex h-11 min-w-0 flex-1 overflow-hidden rounded-lg bg-card ring-primary focus-within:ring-2" role="search" @submit.prevent="submitSearch">
                    <label class="sr-only" for="header-category">{{ t('shopChrome.header.allCategories') }}</label>
                    <div class="relative flex shrink-0 items-center border-r border-slate-200 bg-slate-100 text-slate-700 hover:bg-slate-200">
                        <select
                            id="header-category"
                            v-model="category"
                            class="h-full max-w-[170px] cursor-pointer appearance-none border-0 bg-transparent py-0 pl-3 pr-8 text-sm font-medium text-slate-700 focus:ring-0"
                        >
                            <option value="">{{ t('shopChrome.header.all') }}</option>
                            <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">{{ cat.name }}</option>
                        </select>
                        <ChevronDown class="pointer-events-none absolute right-2.5 h-4 w-4 text-slate-500" />
                    </div>
                    <label class="sr-only" for="header-search">{{ t('shopChrome.header.search') }}</label>
                    <input
                        id="header-search"
                        v-model="query"
                        type="search"
                        :placeholder="t('shopChrome.header.searchPlaceholder')"
                        class="min-w-0 flex-1 border-0 bg-card px-4 text-[15px] text-slate-900 placeholder:text-slate-500 focus:ring-0"
                    />
                    <button
                        type="submit"
                        class="flex w-14 shrink-0 items-center justify-center bg-primary text-white transition hover:bg-primary-hover"
                        :aria-label="t('shopChrome.header.search')"
                    >
                        <Search class="h-5 w-5" />
                    </button>
                </form>

                <div class="flex shrink-0 items-center gap-1">
                    <LanguageSwitcher class="[&_button]:border-white/20 [&_button]:bg-transparent [&_button]:text-white" />
                    <ThemeToggle :floating="false" class="h-10 w-10 rounded-lg border-white/20 bg-transparent text-white hover:bg-white/10 hover:text-white" />

                    <Link
                        :href="user ? route('dashboard') : route('login')"
                        class="rounded-lg border border-transparent px-2.5 py-1.5 leading-tight hover:border-white/40"
                    >
                        <p class="max-w-[150px] truncate text-xs text-white/70">
                            {{ user ? t('shopChrome.header.hello', { name: firstName }) : t('shopChrome.header.helloGuest') }}
                        </p>
                        <p class="flex items-center gap-1 text-sm font-bold">
                            {{ t('shopChrome.header.account') }} <ChevronDown class="h-3.5 w-3.5 text-white/70" />
                        </p>
                    </Link>

                    <Link
                        :href="user ? route('orders.index') : route('login')"
                        class="rounded-lg border border-transparent px-2.5 py-1.5 leading-tight hover:border-white/40"
                    >
                        <p class="text-xs text-white/70">{{ t('shopChrome.header.ordersSub') }}</p>
                        <p class="text-sm font-bold">{{ t('shopChrome.header.orders') }}</p>
                    </Link>

                    <Link
                        :href="route('cart.index')"
                        class="relative flex items-end gap-1.5 rounded-lg border border-transparent px-2.5 py-1.5 hover:border-white/40"
                    >
                        <span class="relative">
                            <ShoppingCart class="h-8 w-8" />
                            <span class="absolute -top-1.5 left-1/2 -translate-x-1/2 text-sm font-black text-orange">{{ cartItemsCount }}</span>
                        </span>
                        <span class="pb-0.5 text-sm font-bold">{{ t('shopChrome.header.cart') }}</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Rangée navigation -->
        <nav class="bg-header-2">
            <div class="mx-auto flex h-10 max-w-[1500px] items-center gap-1 overflow-hidden px-6 text-sm">
                <Link
                    href="/products"
                    class="flex shrink-0 items-center gap-1.5 rounded-md border border-transparent px-2.5 py-1 font-bold hover:border-white/40"
                >
                    <Menu class="h-4 w-4" /> {{ t('shopChrome.header.allCategories') }}
                </Link>
                <Link
                    v-for="link in navLinks"
                    :key="link.href"
                    :href="link.href"
                    class="shrink-0 rounded-md border px-2.5 py-1 font-medium hover:border-white/40"
                    :class="isActive(link.href) ? 'border-white/40' : 'border-transparent'"
                >{{ link.label }}</Link>
                <span class="mx-1 h-4 w-px shrink-0 bg-white/20" />
                <Link
                    v-for="cat in quickCategories"
                    :key="cat.id"
                    :href="route('products.index', { categories: [cat.id] })"
                    class="shrink-0 truncate rounded-md border border-transparent px-2.5 py-1 text-white/85 hover:border-white/40 hover:text-white"
                >{{ cat.name }}</Link>

                <Link
                    :href="isBackofficeUser ? route('dashboard') : route('register')"
                    class="ml-auto flex shrink-0 items-center gap-1.5 rounded-md border border-transparent px-2.5 py-1 font-bold text-orange hover:border-white/40"
                >
                    <component :is="isBackofficeUser ? LayoutDashboard : Package" class="h-4 w-4" />
                    {{ isBackofficeUser ? t('shopChrome.header.backoffice') : t('shopChrome.header.sell') }}
                </Link>
            </div>
        </nav>
    </header>
</template>
