<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ShieldCheck, Truck } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const year = new Date().getFullYear();

const scrollTop = () => window.scrollTo({ top: 0, behavior: 'smooth' });

const columns = computed(() => [
    {
        title: t('shopChrome.footer.discover'),
        links: [
            { label: t('shopChrome.header.products'), href: '/products' },
            { label: t('shopChrome.header.shops'), href: '/shops' },
            { label: t('shopChrome.header.services'), href: '/services' },
        ],
    },
    {
        title: t('shopChrome.footer.account'),
        links: user.value
            ? [
                { label: t('shopChrome.footer.myAccount'), href: route('dashboard') },
                { label: t('shopChrome.footer.myOrders'), href: route('orders.index') },
                { label: t('shopChrome.header.cart'), href: route('cart.index') },
            ]
            : [
                { label: t('shopChrome.header.helloGuest'), href: route('login') },
                { label: t('shopChrome.header.cart'), href: route('cart.index') },
            ],
    },
    {
        title: t('shopChrome.footer.sell'),
        links: [
            { label: t('shopChrome.footer.becomeSeller'), href: route('register') },
            { label: t('shopChrome.footer.sellerSpace'), href: user.value ? route('dashboard') : route('login') },
        ],
    },
    {
        title: t('shopChrome.footer.help'),
        links: [
            { label: t('shopChrome.footer.aboutUs'), href: '/about' },
            { label: t('shopChrome.footer.contact'), href: '/contact' },
        ],
    },
]);
</script>

<template>
    <footer class="mt-12 hidden text-white lg:block">
        <button type="button" class="block w-full bg-header-2 py-3.5 text-sm font-medium hover:bg-header-2/80" @click="scrollTop">
            {{ t('shopChrome.footer.backToTop') }}
        </button>

        <div class="bg-header-2/90">
            <div class="mx-auto grid max-w-[1200px] grid-cols-4 gap-10 px-6 py-12">
                <div v-for="col in columns" :key="col.title">
                    <h3 class="mb-3 text-base font-bold">{{ col.title }}</h3>
                    <ul class="space-y-2">
                        <li v-for="link in col.links" :key="link.label">
                            <Link :href="link.href" class="text-sm text-white/75 hover:text-white hover:underline">{{ link.label }}</Link>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="bg-header">
            <div class="mx-auto flex max-w-[1200px] items-center justify-between gap-6 px-6 py-6 text-sm text-white/70">
                <Link href="/" class="flex items-center gap-2 text-white">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary font-display text-sm font-black">M</span>
                    <span class="font-display text-lg font-extrabold">Maketu<span class="text-primary">Shop</span></span>
                </Link>
                <div class="flex items-center gap-6">
                    <span class="flex items-center gap-2"><ShieldCheck class="h-4 w-4 text-shop-green" /> {{ t('shopChrome.footer.securePayment') }}</span>
                    <span class="flex items-center gap-2"><Truck class="h-4 w-4 text-orange" /> {{ t('shopChrome.footer.delivery') }}</span>
                </div>
                <p>© {{ year }} MaketuShop. {{ t('shopChrome.footer.rights') }}</p>
            </div>
        </div>
    </footer>
</template>
