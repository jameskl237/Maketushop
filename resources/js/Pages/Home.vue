<script setup>
import BottomNav from '@/components/shop/BottomNav.vue';
import TopBar from '@/components/shop/TopBar.vue';
import StarRating from '@/components/StarRating.vue';
import { useCart } from '@/composables/useCart';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    ChevronLeft,
    ChevronRight,
    Flame,
    ShoppingBag,
    Sparkles,
    Store,
    Tag,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    heroProducts:  { type: Array, default: () => [] },
    categories:    { type: Array, default: () => [] },
    newArrivals:   { type: Array, default: () => [] },
    popular:       { type: Array, default: () => [] },
    shops:         { type: Array, default: () => [] },
});

const { t } = useI18n();
const { addToCart } = useCart();

/* ── Hero slider ── */
const heroIndex = ref(0);
let heroTimer = null;

const heroSlides = computed(() =>
    props.heroProducts.length ? props.heroProducts : Array.from({ length: 3 }, (_, i) => ({
        id: i,
        name: 'Produit tendance',
        main_image: `/images/Maketu${i % 2 === 0 ? '1' : '_coeur'}.png`,
        price: 5000 + i * 1000,
        category: { name: 'Artisanat' },
        shop: { name: 'MaketuShop' },
    }))
);

const nextHero = () => { heroIndex.value = (heroIndex.value + 1) % heroSlides.value.length; };
const prevHero = () => { heroIndex.value = (heroIndex.value - 1 + heroSlides.value.length) % heroSlides.value.length; };

onMounted(() => { heroTimer = setInterval(nextHero, 4000); });
onUnmounted(() => clearInterval(heroTimer));

/* ── Category image fallback map ── */
const CAT_IMAGES = {
    artisanat: '/images/artisanat.png',
    maison: '/images/maison.png',
    mode: '/images/mode.png',
    beauté: '/images/beaute.png',
    beaute: '/images/beaute.png',
    électronique: '/images/electronique.png',
    electronique: '/images/electronique.png',
    alimentation: '/images/alimentation.png',
    sport: '/images/sport.png',
    accessoires: '/images/accessoire.png',
};

const catImage = (cat) => {
    if (cat.image) return cat.image;
    const k = (cat.name || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    return CAT_IMAGES[k] || CAT_IMAGES[(cat.name || '').toLowerCase()] || '/images/Maketu1.png';
};

/* ── Price formatter ── */
const fmt = (v) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 }).format(v ?? 0);

const goCategory = (id) => router.visit(route('products.index', { categories: [id] }));
</script>

<template>
    <Head title="Accueil" />

    <div class="min-h-screen bg-background pb-24 text-foreground">
        <TopBar />

        <main class="animate-reveal-fade mx-auto max-w-2xl space-y-0 lg:max-w-none">

            <!-- ═══════════════════════════════════════════════
                 HERO SLIDER
            ═══════════════════════════════════════════════ -->
            <section class="relative h-[58vw] max-h-[460px] min-h-[260px] overflow-hidden bg-[#0b0820]">
                <!-- Slides -->
                <TransitionGroup name="hero-slide" tag="div" class="relative h-full w-full">
                    <div
                        v-for="(slide, idx) in heroSlides"
                        v-show="idx === heroIndex"
                        :key="slide.id"
                        class="absolute inset-0"
                    >
                        <img
                            :src="slide.main_image"
                            :alt="slide.name"
                            class="h-full w-full object-cover"
                        />
                        <!-- Dark gradient -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />
                        <div class="absolute inset-0 bg-gradient-to-r from-black/40 to-transparent" />

                        <!-- Content -->
                        <div class="absolute bottom-0 left-0 right-0 p-4 sm:p-6">
                            <div class="max-w-sm">
                                <p v-if="slide.category" class="mb-1 text-[10px] font-bold uppercase tracking-widest text-white/60">
                                    {{ slide.category.name }}
                                </p>
                                <h2 class="font-display text-xl font-black leading-tight text-white sm:text-2xl line-clamp-2">
                                    {{ slide.name }}
                                </h2>
                                <div class="mt-2 flex items-center gap-3">
                                    <span class="text-lg font-black text-white">{{ fmt(slide.price) }}</span>
                                    <Link
                                        :href="route('products.show', { product: slide.id })"
                                        class="flex items-center gap-1.5 rounded-2xl bg-primary px-4 py-2 text-xs font-bold text-white shadow-sm active:scale-95 transition-transform"
                                    >
                                        Voir <ArrowRight class="h-3.5 w-3.5" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </TransitionGroup>

                <!-- Prev / Next -->
                <button
                    class="absolute left-3 top-1/2 -translate-y-1/2 flex h-8 w-8 items-center justify-center rounded-full bg-black/30 text-white backdrop-blur-sm active:scale-90 transition-transform"
                    @click="prevHero"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>
                <button
                    class="absolute right-3 top-1/2 -translate-y-1/2 flex h-8 w-8 items-center justify-center rounded-full bg-black/30 text-white backdrop-blur-sm active:scale-90 transition-transform"
                    @click="nextHero"
                >
                    <ChevronRight class="h-4 w-4" />
                </button>

                <!-- Dots -->
                <div class="absolute bottom-3 right-4 flex gap-1.5">
                    <button
                        v-for="(_, i) in heroSlides"
                        :key="i"
                        class="rounded-full transition-all duration-300"
                        :class="i === heroIndex ? 'w-5 h-2 bg-white' : 'w-2 h-2 bg-white/40'"
                        @click="heroIndex = i"
                    />
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════
                 QUICK ACTIONS
            ═══════════════════════════════════════════════ -->
            <section class="px-4 pt-4">
                <div class="grid grid-cols-4 gap-2">
                    <Link :href="route('products.index')" class="flex flex-col items-center gap-1.5 rounded-2xl bg-primary/8 p-3 active:scale-95 transition-transform">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white shadow-sm">
                            <ShoppingBag class="h-5 w-5" />
                        </div>
                        <span class="text-[10px] font-semibold text-foreground">Produits</span>
                    </Link>
                    <Link :href="route('shops.index')" class="flex flex-col items-center gap-1.5 rounded-2xl bg-orange/8 p-3 active:scale-95 transition-transform">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange text-white shadow-sm">
                            <Store class="h-5 w-5" />
                        </div>
                        <span class="text-[10px] font-semibold text-foreground">Boutiques</span>
                    </Link>
                    <Link :href="route('products.index', { sort: 'popular' })" class="flex flex-col items-center gap-1.5 rounded-2xl bg-amber/8 p-3 active:scale-95 transition-transform">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber text-white shadow-sm">
                            <Flame class="h-5 w-5" />
                        </div>
                        <span class="text-[10px] font-semibold text-foreground">Tendances</span>
                    </Link>
                    <Link :href="route('products.index', { sort: 'newest' })" class="flex flex-col items-center gap-1.5 rounded-2xl bg-shop-green/8 p-3 active:scale-95 transition-transform">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-shop-green text-white shadow-sm">
                            <Sparkles class="h-5 w-5" />
                        </div>
                        <span class="text-[10px] font-semibold text-foreground">Nouveaux</span>
                    </Link>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════
                 CATEGORIES HORIZONTAL SCROLL
            ═══════════════════════════════════════════════ -->
            <section v-if="categories.length" class="pt-5">
                <div class="mb-3 flex items-center justify-between px-4">
                    <div class="flex items-center gap-2">
                        <Tag class="h-4 w-4 text-primary" />
                        <h2 class="font-display text-[15px] font-black text-foreground">Catégories</h2>
                    </div>
                    <Link :href="route('products.index')" class="text-[11px] font-bold text-primary">Tout voir</Link>
                </div>

                <div class="flex gap-2.5 overflow-x-auto px-4 pb-1 no-scrollbar">
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        class="group relative flex-shrink-0 w-[88px] overflow-hidden rounded-2xl active:scale-95 transition-transform"
                        @click="goCategory(cat.id)"
                    >
                        <div class="relative h-[88px] w-[88px]">
                            <img
                                :src="catImage(cat)"
                                :alt="cat.name"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent" />
                            <p class="absolute bottom-2 left-0 right-0 text-center text-[9px] font-black leading-tight text-white px-1">
                                {{ cat.name }}
                            </p>
                        </div>
                    </button>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════
                 NOUVEAUTÉS
            ═══════════════════════════════════════════════ -->
            <section v-if="newArrivals.length" class="pt-5">
                <div class="mb-3 flex items-center justify-between px-4">
                    <div class="flex items-center gap-2">
                        <Sparkles class="h-4 w-4 text-primary" />
                        <h2 class="font-display text-[15px] font-black text-foreground">Nouveautés</h2>
                    </div>
                    <Link :href="route('products.index', { sort: 'newest' })" class="text-[11px] font-bold text-primary">Tout voir</Link>
                </div>

                <div class="flex gap-3 overflow-x-auto px-4 pb-1 no-scrollbar">
                    <ProductMiniCard
                        v-for="product in newArrivals"
                        :key="product.id"
                        :product="product"
                        @add-cart="addToCart(product, 1)"
                    />
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════
                 TENDANCES / POPULAIRES — 2 colonnes
            ═══════════════════════════════════════════════ -->
            <section v-if="popular.length" class="pt-5">
                <div class="mb-3 flex items-center justify-between px-4">
                    <div class="flex items-center gap-2">
                        <Flame class="h-4 w-4 text-orange" />
                        <h2 class="font-display text-[15px] font-black text-foreground">Tendances</h2>
                    </div>
                    <Link :href="route('products.index', { sort: 'popular' })" class="text-[11px] font-bold text-primary">Tout voir</Link>
                </div>

                <div class="grid grid-cols-2 gap-2.5 px-4 sm:grid-cols-3 lg:grid-cols-4">
                    <ProductGridCard
                        v-for="product in popular"
                        :key="product.id"
                        :product="product"
                        @add-cart="addToCart(product, 1)"
                    />
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════
                 BOUTIQUES POPULAIRES
            ═══════════════════════════════════════════════ -->
            <section v-if="shops.length" class="pt-5 pb-4">
                <div class="mb-3 flex items-center justify-between px-4">
                    <div class="flex items-center gap-2">
                        <Store class="h-4 w-4 text-orange" />
                        <h2 class="font-display text-[15px] font-black text-foreground">Boutiques</h2>
                    </div>
                    <Link :href="route('shops.index')" class="text-[11px] font-bold text-primary">Tout voir</Link>
                </div>

                <div class="flex gap-3 overflow-x-auto px-4 pb-1 no-scrollbar">
                    <Link
                        v-for="shop in shops"
                        :key="shop.id"
                        :href="route('shops.show', { shop: shop.id })"
                        class="glass-card flex-shrink-0 w-44 p-3 active:scale-95 transition-transform"
                    >
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-primary/10 overflow-hidden">
                                <img v-if="shop.logo" :src="shop.logo" :alt="shop.name" class="h-full w-full object-cover" />
                                <span v-else class="font-display text-sm font-black text-primary">{{ shop.name?.[0] }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-[12px] font-bold text-foreground">{{ shop.name }}</p>
                                <p class="text-[10px] text-muted-foreground">{{ shop.products_count }} produits</p>
                            </div>
                        </div>
                        <p v-if="shop.city" class="mt-2 text-[10px] text-muted-foreground truncate">📍 {{ shop.city }}</p>
                    </Link>
                </div>
            </section>

        </main>

        <BottomNav />
    </div>
</template>

<!-- ── Sub-components inline ── -->
<script>
import { defineComponent, computed, h } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ShoppingBag } from 'lucide-vue-next';
import StarRating from '@/components/StarRating.vue';

const ProductMiniCard = defineComponent({
    name: 'ProductMiniCard',
    props: { product: Object },
    emits: ['add-cart'],
    setup(props, { emit }) {
        const fmt = (v) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 }).format(v ?? 0);
        return () => h(
            Link,
            { href: route('products.show', { product: props.product.id }), class: 'flex-shrink-0 w-[140px] block' },
            () => [
                h('div', { class: 'relative aspect-square w-full overflow-hidden rounded-2xl bg-muted' }, [
                    h('img', {
                        src: props.product.main_image || '/images/Maketu1.png',
                        alt: props.product.name,
                        class: 'h-full w-full object-cover',
                        loading: 'lazy',
                    }),
                    props.product.is_new && h('span', { class: 'absolute left-2 top-2 rounded-xl bg-primary px-2 py-0.5 text-[9px] font-bold text-white' }, 'New'),
                    h('div', { class: 'absolute bottom-2 right-2 rounded-xl border border-white/40 bg-white/70 px-2 py-1 backdrop-blur-md' }, [
                        h('p', { class: 'font-display text-[11px] font-black text-primary leading-none' }, fmt(props.product.price)),
                    ]),
                ]),
                h('div', { class: 'mt-2 space-y-0.5 px-0.5' }, [
                    h('p', { class: 'line-clamp-1 text-[12px] font-bold text-foreground' }, props.product.name),
                    h('p', { class: 'text-[10px] text-muted-foreground' }, props.product.shop?.name),
                ]),
            ]
        );
    },
});

const ProductGridCard = defineComponent({
    name: 'ProductGridCard',
    props: { product: Object },
    emits: ['add-cart'],
    setup(props, { emit }) {
        const fmt = (v) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 }).format(v ?? 0);
        return () => h('div', { class: 'group rounded-3xl overflow-hidden bg-card shadow-sm active:scale-[0.98] transition-transform' }, [
            h(Link, { href: route('products.show', { product: props.product.id }), class: 'block' }, () => [
                h('div', { class: 'relative aspect-square overflow-hidden bg-muted' }, [
                    h('img', {
                        src: props.product.main_image || '/images/Maketu1.png',
                        alt: props.product.name,
                        class: 'h-full w-full object-cover transition-transform duration-500 group-hover:scale-105',
                        loading: 'lazy',
                    }),
                    props.product.is_new && h('span', { class: 'absolute left-2.5 top-2.5 rounded-xl bg-primary px-2 py-0.5 text-[9px] font-bold text-white' }, 'New'),
                    h('div', { class: 'absolute bottom-2 right-2 rounded-xl border border-white/40 bg-white/70 px-2.5 py-1.5 backdrop-blur-md shadow-sm' }, [
                        h('p', { class: 'font-display text-[12px] font-black text-primary leading-none' }, fmt(props.product.price)),
                    ]),
                ]),
            ]),
            h('div', { class: 'p-2.5 space-y-1' }, [
                h(Link, { href: route('products.show', { product: props.product.id }), class: 'line-clamp-1 text-[12px] font-bold text-foreground' }, () => props.product.name),
                h('div', { class: 'flex items-center justify-between' }, [
                    h('p', { class: 'text-[10px] text-muted-foreground truncate' }, props.product.shop?.name),
                    props.product.average_rating > 0 && h('div', { class: 'flex items-center gap-0.5 text-[10px] font-bold text-amber-400' }, [
                        h('svg', { class: 'h-2.5 w-2.5 fill-current', viewBox: '0 0 20 20' },
                            h('path', { d: 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z' })
                        ),
                        h('span', {}, props.product.average_rating.toFixed(1)),
                    ]),
                ]),
                h('button', {
                    class: 'mt-1.5 flex w-full items-center justify-center gap-1.5 rounded-2xl bg-primary py-2 text-[11px] font-bold text-white active:scale-95 transition-transform disabled:opacity-50',
                    disabled: props.product.stock <= 0,
                    onClick: (e) => { e.preventDefault(); emit('add-cart'); },
                }, [
                    h(ShoppingBag, { class: 'h-3.5 w-3.5' }),
                    props.product.stock > 0 ? 'Ajouter' : 'Rupture',
                ]),
            ]),
        ]);
    },
});

export default {
    components: { ProductMiniCard, ProductGridCard },
};
</script>

<style scoped>
.hero-slide-enter-active,
.hero-slide-leave-active {
    transition: opacity 0.6s ease;
}
.hero-slide-enter-from,
.hero-slide-leave-to {
    opacity: 0;
}

/* bg opacity utilities not in tailwind v3 */
.bg-primary\/8  { background-color: rgb(var(--primary-rgb) / 0.08); }
.bg-orange\/8   { background-color: rgb(var(--orange-rgb)  / 0.08); }
.bg-amber\/8    { background-color: rgb(var(--amber-rgb)   / 0.08); }
.bg-shop-green\/8 { background-color: rgb(var(--green-rgb) / 0.08); }
</style>
