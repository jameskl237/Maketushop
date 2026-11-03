<script setup>
import ProductCard from '@/components/products/listing/ProductCard.vue';
import BottomNav from '@/components/shop/BottomNav.vue';
import TopBar from '@/components/shop/TopBar.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Briefcase,
    ChevronLeft,
    ChevronRight,
    Flame,
    MapPin,
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

// Les listes peuvent arriver sous forme d'objet JSON ({"1": …}) si le serveur
// filtre une collection sans la réindexer : on normalise toujours en tableau.
const asList = (value) => (Array.isArray(value) ? value : Object.values(value ?? {}));
const categoryList = computed(() => asList(props.categories));
const newArrivalList = computed(() => asList(props.newArrivals));
const popularList = computed(() => asList(props.popular));
const shopList = computed(() => asList(props.shops));

/* ── Hero slider ── */
const heroIndex = ref(0);
let heroTimer = null;

const heroSlides = computed(() =>
    asList(props.heroProducts).length ? asList(props.heroProducts) : Array.from({ length: 3 }, (_, i) => ({
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

    <div class="min-h-screen bg-background text-foreground">
        <TopBar />

        <main class="animate-reveal-fade">

            <!-- ═══════════ HERO SLIDER ═══════════ -->
            <section class="relative h-[58vw] max-h-[460px] min-h-[260px] overflow-hidden bg-header lg:h-[440px] lg:max-h-none">
                <TransitionGroup name="hero-slide" tag="div" class="relative h-full w-full">
                    <div
                        v-for="(slide, idx) in heroSlides"
                        v-show="idx === heroIndex"
                        :key="slide.id"
                        class="absolute inset-0"
                    >
                        <img :src="slide.main_image" :alt="slide.name" class="h-full w-full object-cover" />
                        <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/30 to-transparent" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent lg:from-background lg:via-background/10" />

                        <div class="absolute inset-x-0 bottom-0 p-4 sm:p-6 lg:bottom-auto lg:top-10">
                            <div class="mx-auto max-w-[1500px] lg:px-10">
                                <div class="max-w-md">
                                    <p v-if="slide.category" class="mb-1.5 text-xs font-bold uppercase tracking-widest text-white/75">
                                        {{ slide.category.name }}
                                    </p>
                                    <h2 class="line-clamp-2 font-display text-2xl font-black leading-tight text-white drop-shadow sm:text-3xl lg:text-4xl xl:text-5xl">
                                        {{ slide.name }}
                                    </h2>
                                    <div class="mt-3 flex items-center gap-3 lg:mt-5">
                                        <span class="text-xl font-black text-white lg:text-2xl">{{ fmt(slide.price) }}</span>
                                        <Link
                                            :href="route('products.show', { product: slide.id })"
                                            class="flex items-center gap-1.5 rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-hover active:scale-95"
                                        >
                                            Voir <ArrowRight class="h-4 w-4" />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </TransitionGroup>

                <button
                    class="absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-white backdrop-blur-sm transition hover:bg-black/55 active:scale-90 lg:left-6 lg:top-[38%] lg:h-14 lg:w-10 lg:rounded-lg"
                    aria-label="Précédent"
                    @click="prevHero"
                >
                    <ChevronLeft class="h-5 w-5 lg:h-7 lg:w-7" />
                </button>
                <button
                    class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-white backdrop-blur-sm transition hover:bg-black/55 active:scale-90 lg:right-6 lg:top-[38%] lg:h-14 lg:w-10 lg:rounded-lg"
                    aria-label="Suivant"
                    @click="nextHero"
                >
                    <ChevronRight class="h-5 w-5 lg:h-7 lg:w-7" />
                </button>

                <div class="absolute bottom-3 right-4 flex gap-1.5 lg:hidden">
                    <button
                        v-for="(_, i) in heroSlides"
                        :key="i"
                        class="rounded-full transition-all duration-300"
                        :class="i === heroIndex ? 'h-2 w-5 bg-card' : 'h-2 w-2 bg-white/50'"
                        :aria-label="`Diapositive ${i + 1}`"
                        @click="heroIndex = i"
                    />
                </div>
            </section>

            <div class="mx-auto max-w-[1500px] lg:px-6">

                <!-- ═══════════ DESKTOP : tuiles façon marketplace (chevauchent le hero) ═══════════ -->
                <section class="relative z-10 hidden gap-5 lg:-mt-44 lg:grid lg:grid-cols-4">
                    <div class="flex flex-col rounded-xl border border-border bg-card p-5 shadow-sm">
                        <h3 class="mb-3 font-display text-lg font-extrabold">Explorez par catégorie</h3>
                        <div class="grid flex-1 grid-cols-2 gap-3">
                            <button
                                v-for="cat in categoryList.slice(0, 4)"
                                :key="cat.id"
                                type="button"
                                class="group text-left"
                                @click="goCategory(cat.id)"
                            >
                                <div class="aspect-square overflow-hidden rounded-lg bg-muted">
                                    <img :src="catImage(cat)" :alt="cat.name" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" />
                                </div>
                                <p class="mt-1.5 line-clamp-1 text-xs font-medium text-shop-muted group-hover:text-primary">{{ cat.name }}</p>
                            </button>
                        </div>
                        <Link :href="route('products.index')" class="mt-3 text-sm font-semibold text-primary hover:underline">Voir toutes les catégories</Link>
                    </div>

                    <div class="flex flex-col rounded-xl border border-border bg-card p-5 shadow-sm">
                        <h3 class="mb-3 font-display text-lg font-extrabold">Nouveautés de la semaine</h3>
                        <div class="grid flex-1 grid-cols-2 gap-3">
                            <Link
                                v-for="product in newArrivalList.slice(0, 4)"
                                :key="product.id"
                                :href="route('products.show', { product: product.id })"
                                class="group"
                            >
                                <div class="aspect-square overflow-hidden rounded-lg bg-muted">
                                    <img :src="product.main_image || '/images/Maketu1.png'" :alt="product.name" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" />
                                </div>
                                <p class="mt-1.5 line-clamp-1 text-xs font-medium text-shop-muted group-hover:text-primary">{{ product.name }}</p>
                            </Link>
                        </div>
                        <Link :href="route('products.index', { sort: 'newest' })" class="mt-3 text-sm font-semibold text-primary hover:underline">Découvrir les nouveautés</Link>
                    </div>

                    <div class="flex flex-col rounded-xl border border-border bg-card p-5 shadow-sm">
                        <h3 class="mb-3 font-display text-lg font-extrabold">Boutiques à découvrir</h3>
                        <ul class="flex-1 space-y-2.5">
                            <li v-for="shop in shopList.slice(0, 4)" :key="shop.id">
                                <Link :href="route('shops.show', { shop: shop.id })" class="group flex items-center gap-3 rounded-lg p-1.5 hover:bg-muted">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-primary/10">
                                        <img v-if="shop.logo" :src="shop.logo" :alt="shop.name" class="h-full w-full object-cover" />
                                        <span v-else class="font-display text-base font-black text-primary">{{ shop.name?.[0] }}</span>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold group-hover:text-primary">{{ shop.name }}</span>
                                        <span class="block truncate text-xs text-shop-light">{{ shop.products_count }} produits<template v-if="shop.city"> · {{ shop.city }}</template></span>
                                    </span>
                                </Link>
                            </li>
                        </ul>
                        <Link :href="route('shops.index')" class="mt-3 text-sm font-semibold text-primary hover:underline">Toutes les boutiques</Link>
                    </div>

                    <div class="flex flex-col justify-between rounded-xl bg-gradient-to-br from-primary to-orange p-6 text-white shadow-sm">
                        <div>
                            <Store class="h-9 w-9" />
                            <h3 class="mt-4 font-display text-2xl font-extrabold leading-tight">Vendez sur MaketuShop</h3>
                            <p class="mt-2 text-sm text-white/90">Ouvrez votre boutique en quelques minutes et touchez des milliers de clients au Cameroun. Paiement Mobile Money sécurisé.</p>
                        </div>
                        <div class="mt-5 flex flex-col gap-2">
                            <Link :href="route('register')" class="rounded-full bg-card py-2.5 text-center text-sm font-bold text-primary transition hover:bg-white/90">Créer ma boutique</Link>
                            <Link :href="route('services.index')" class="rounded-full border border-white/60 py-2.5 text-center text-sm font-bold text-white transition hover:bg-white/10">
                                <Briefcase class="mr-1 inline h-4 w-4" /> Trouver un prestataire
                            </Link>
                        </div>
                    </div>
                </section>

                <!-- ═══════════ MOBILE : accès principaux ═══════════ -->
                <section class="px-4 pt-4 lg:hidden">
                    <div class="grid grid-cols-2 gap-3">
                        <Link
                            :href="route('products.index')"
                            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary to-primary-hover p-4 text-white shadow-lg shadow-primary/20 transition-transform active:scale-[0.98]"
                        >
                            <ShoppingBag class="h-7 w-7" />
                            <p class="mt-3 font-display text-base font-extrabold">Produits</p>
                            <p class="text-xs text-white/90">Achetez près de chez vous</p>
                        </Link>
                        <Link
                            :href="route('services.index')"
                            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange to-amber-500 p-4 text-white shadow-lg shadow-orange/20 transition-transform active:scale-[0.98]"
                        >
                            <Briefcase class="h-7 w-7" />
                            <p class="mt-3 font-display text-base font-extrabold">Services</p>
                            <p class="text-xs text-white/90">Trouvez un prestataire</p>
                        </Link>
                    </div>
                </section>

                <!-- ═══════════ CATÉGORIES ═══════════ -->
                <section v-if="categoryList.length" class="pt-6 lg:mt-6 lg:rounded-xl lg:border lg:border-border lg:bg-card lg:p-5">
                    <SectionTitle :icon="Tag" title="Catégories" :href="route('products.index')" />
                    <div class="no-scrollbar flex gap-3 overflow-x-auto px-4 pb-1 lg:grid lg:grid-cols-8 lg:gap-4 lg:overflow-visible lg:px-0">
                        <button
                            v-for="cat in categoryList"
                            :key="cat.id"
                            class="group relative w-[92px] flex-shrink-0 overflow-hidden rounded-2xl transition-transform active:scale-95 lg:w-auto lg:rounded-xl"
                            @click="goCategory(cat.id)"
                        >
                            <div class="relative aspect-square w-full">
                                <img :src="catImage(cat)" :alt="cat.name" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" loading="lazy" />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />
                                <p class="absolute bottom-2 left-0 right-0 px-1.5 text-center text-[12px] font-bold leading-tight text-white lg:text-sm">
                                    {{ cat.name }}
                                </p>
                            </div>
                        </button>
                    </div>
                </section>

                <!-- ═══════════ NOUVEAUTÉS ═══════════ -->
                <section v-if="newArrivalList.length" class="pt-6 lg:mt-6 lg:rounded-xl lg:border lg:border-border lg:bg-card lg:p-5">
                    <SectionTitle :icon="Sparkles" title="Nouveautés" :href="route('products.index', { sort: 'newest' })" />
                    <div class="no-scrollbar flex gap-3 overflow-x-auto px-4 pb-1 lg:gap-4 lg:px-0">
                        <ProductMiniCard
                            v-for="product in newArrivalList"
                            :key="product.id"
                            :product="product"
                        />
                    </div>
                </section>

                <!-- ═══════════ TENDANCES ═══════════ -->
                <section v-if="popularList.length" class="px-4 pt-6 lg:mt-6 lg:px-0">
                    <SectionTitle :icon="Flame" title="Tendances" :href="route('products.index', { sort: 'popular' })" flush />
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        <ProductCard v-for="product in popularList" :key="product.id" :product="product" />
                    </div>
                </section>

                <!-- ═══════════ BOUTIQUES ═══════════ -->
                <section v-if="shopList.length" class="pb-4 pt-6 lg:mt-6 lg:rounded-xl lg:border lg:border-border lg:bg-card lg:p-5">
                    <SectionTitle :icon="Store" title="Boutiques" :href="route('shops.index')" />
                    <div class="no-scrollbar flex gap-3 overflow-x-auto px-4 pb-1 lg:grid lg:grid-cols-4 lg:gap-4 lg:overflow-visible lg:px-0 xl:grid-cols-6">
                        <Link
                            v-for="shop in shopList"
                            :key="shop.id"
                            :href="route('shops.show', { shop: shop.id })"
                            class="w-48 flex-shrink-0 rounded-xl border border-border bg-card p-3 transition hover:border-primary/40 hover:shadow-md active:scale-95 lg:w-auto lg:bg-background"
                        >
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center overflow-hidden rounded-xl bg-primary/10">
                                    <img v-if="shop.logo" :src="shop.logo" :alt="shop.name" class="h-full w-full object-cover" />
                                    <span v-else class="font-display text-base font-black text-primary">{{ shop.name?.[0] }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-foreground">{{ shop.name }}</p>
                                    <p class="text-xs text-shop-muted">{{ shop.products_count }} produits</p>
                                </div>
                            </div>
                            <p v-if="shop.city" class="mt-2 flex items-center gap-1 truncate text-xs text-shop-muted">
                                <MapPin class="h-3.5 w-3.5 shrink-0" /> {{ shop.city }}
                            </p>
                        </Link>
                    </div>
                </section>
            </div>
        </main>

        <BottomNav />
    </div>
</template>

<!-- ── Sous-composants ── -->
<script>
import { defineComponent, h } from 'vue';
import { Link } from '@inertiajs/vue3';

const fmtPrice = (v) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 }).format(v ?? 0);

const SectionTitle = defineComponent({
    name: 'SectionTitle',
    props: { icon: [Object, Function], title: String, href: String, flush: Boolean },
    setup(props) {
        return () => h('div', { class: ['mb-3 flex items-center justify-between lg:mb-4', props.flush ? '' : 'px-4 lg:px-0'] }, [
            h('div', { class: 'flex items-center gap-2' }, [
                props.icon && h(props.icon, { class: 'h-5 w-5 text-primary' }),
                h('h2', { class: 'font-display text-lg font-extrabold text-foreground lg:text-xl' }, props.title),
            ]),
            props.href && h(Link, { href: props.href, class: 'text-sm font-semibold text-primary hover:underline' }, () => 'Tout voir'),
        ]);
    },
});

const ProductMiniCard = defineComponent({
    name: 'ProductMiniCard',
    props: { product: Object },
    setup(props) {
        return () => h(
            Link,
            { href: route('products.show', { product: props.product.id }), class: 'group block w-[150px] flex-shrink-0 lg:w-[200px]' },
            () => [
                h('div', { class: 'relative aspect-square w-full overflow-hidden rounded-xl bg-muted' }, [
                    h('img', {
                        src: props.product.main_image || '/images/Maketu1.png',
                        alt: props.product.name,
                        class: 'h-full w-full object-cover transition-transform duration-300 group-hover:scale-105',
                        loading: 'lazy',
                    }),
                    props.product.is_new && h('span', { class: 'absolute left-2 top-2 rounded-md bg-primary px-2 py-0.5 text-[12px] font-bold text-white' }, 'Nouveau'),
                ]),
                h('div', { class: 'mt-2 space-y-0.5 px-0.5' }, [
                    h('p', { class: 'line-clamp-1 text-sm font-semibold text-foreground group-hover:text-primary' }, props.product.name),
                    h('p', { class: 'text-base font-extrabold text-foreground' }, fmtPrice(props.product.price)),
                    h('p', { class: 'line-clamp-1 text-xs text-shop-muted' }, props.product.shop?.name),
                ]),
            ]
        );
    },
});

export default {
    components: { ProductMiniCard, SectionTitle },
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
