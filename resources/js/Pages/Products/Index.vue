<script setup>
import ProductCard from '@/components/products/listing/ProductCard.vue';
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import BottomNav from '@/components/shop/BottomNav.vue';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useFilters } from '@/composables/useFilters';
import { useSearch } from '@/composables/useSearch';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, SlidersHorizontal, X } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, required: true },
    availableCategories: { type: Array, default: () => [] },
    availableLocations: { type: Array, default: () => [] },
});

const loading = ref(false);
const { t } = useI18n();

const state = reactive({
    search: props.filters.search || '',
    categories: [...(props.filters.categories || [])].map(Number),
    locations: [...(props.filters.locations || [])],
    price_min: props.filters.price_min || '',
    price_max: props.filters.price_max || '',
    in_stock: Boolean(props.filters.in_stock),
    sort: props.filters.sort || 'newest',
});

const { hasActiveFilters, toQueryParams } = useFilters(state);
const { search } = useSearch(() => applyFilters(), 500);

const sortOptions = computed(() => [
    { value: 'newest', label: t('productsPage.sort.newest') },
    { value: 'popular', label: t('productsPage.sort.popular') },
    { value: 'price_asc', label: t('productsPage.sort.priceAsc') },
    { value: 'price_desc', label: t('productsPage.sort.priceDesc') },
]);

const total = computed(() => props.products.total ?? props.products.data?.length ?? 0);
const activeCategoryName = computed(() =>
    props.availableCategories.find((c) => Number(c.id) === state.categories[0])?.name,
);

const applyFilters = () => {
    loading.value = true;
    router.get(route('products.index'), toQueryParams(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

watch(() => state.search, () => search());
watch(() => [state.categories, state.locations, state.in_stock, state.sort], () => applyFilters(), { deep: true });

const selectCategory = (categoryId) => {
    state.categories = categoryId === null || state.categories.includes(categoryId) ? [] : [categoryId];
};

const toggleLocation = (city) => {
    state.locations = state.locations.includes(city)
        ? state.locations.filter((c) => c !== city)
        : [...state.locations, city];
};

const resetFilters = () => {
    Object.assign(state, { search: '', categories: [], locations: [], price_min: '', price_max: '', in_stock: false, sort: 'newest' });
};
</script>

<template>
    <Head :title="t('productsPage.headTitle')" />

    <div class="min-h-screen bg-background text-foreground">
        <ProductsNavbar />

        <main class="mx-auto max-w-[1500px] px-3 py-4 sm:px-4 lg:px-6 lg:py-6">
            <!-- ── Mobile : titre, recherche, pastilles ── -->
            <section class="space-y-3 lg:hidden">
                <h1 class="font-display text-2xl font-extrabold leading-tight">{{ t('productsPage.title') }}</h1>
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-shop-light" />
                    <Input
                        v-model="state.search"
                        type="search"
                        class="h-11 rounded-xl border-border bg-card pl-10 pr-10 text-base shadow-none placeholder:text-shop-light"
                        :placeholder="t('productsPage.searchPlaceholder')"
                    />
                    <button
                        v-if="state.search"
                        type="button"
                        class="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-shop-muted hover:bg-muted"
                        @click="state.search = ''"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <ScrollArea>
                    <div class="flex min-w-max gap-2 pb-1">
                        <button
                            type="button"
                            class="h-8 rounded-full border px-3.5 text-[13px] font-medium"
                            :class="!state.categories.length ? 'border-primary bg-primary text-white' : 'border-border bg-card text-shop-muted'"
                            @click="selectCategory(null)"
                        >{{ t('shopChrome.header.all') }}</button>
                        <button
                            v-for="category in availableCategories"
                            :key="category.id"
                            type="button"
                            class="h-8 rounded-full border px-3.5 text-[13px] font-medium"
                            :class="state.categories.includes(Number(category.id)) ? 'border-primary bg-primary text-white' : 'border-border bg-card text-shop-muted'"
                            @click="selectCategory(Number(category.id))"
                        >{{ category.name }}</button>
                    </div>
                </ScrollArea>
            </section>

            <div class="mt-3 lg:mt-0 lg:flex lg:gap-6">
                <!-- ── Desktop : filtres ── -->
                <aside class="hidden w-64 shrink-0 lg:block">
                    <div class="sticky top-[120px] space-y-6 rounded-xl border border-border bg-card p-5">
                        <div class="flex items-center justify-between">
                            <h2 class="flex items-center gap-2 text-base font-bold">
                                <SlidersHorizontal class="h-4 w-4" /> {{ t('productsPage.filters') }}
                            </h2>
                            <button v-if="hasActiveFilters" type="button" class="text-sm font-medium text-primary hover:underline" @click="resetFilters">
                                {{ t('productsPage.reset') }}
                            </button>
                        </div>

                        <div>
                            <h3 class="mb-2 text-sm font-bold">{{ t('productsPage.category') }}</h3>
                            <ul class="max-h-72 space-y-0.5 overflow-y-auto pr-1 text-sm">
                                <li>
                                    <button
                                        type="button"
                                        class="w-full rounded-md px-2 py-1.5 text-left hover:bg-muted"
                                        :class="!state.categories.length ? 'font-bold text-primary' : 'text-shop-muted'"
                                        @click="selectCategory(null)"
                                    >{{ t('shopChrome.header.allCategories') }}</button>
                                </li>
                                <li v-for="category in availableCategories" :key="category.id">
                                    <button
                                        type="button"
                                        class="w-full rounded-md px-2 py-1.5 text-left hover:bg-muted"
                                        :class="state.categories.includes(Number(category.id)) ? 'font-bold text-primary' : 'text-shop-muted'"
                                        @click="selectCategory(Number(category.id))"
                                    >{{ category.name }}</button>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="mb-2 text-sm font-bold">{{ t('productsPage.price') }}</h3>
                            <form class="flex items-center gap-2" @submit.prevent="applyFilters">
                                <input
                                    v-model="state.price_min"
                                    type="number"
                                    min="0"
                                    :placeholder="t('productsPage.min')"
                                    class="h-9 w-full min-w-0 rounded-lg border-border bg-background px-2.5 text-sm text-foreground placeholder:text-shop-light focus:border-primary focus:ring-primary"
                                />
                                <span class="text-shop-light">–</span>
                                <input
                                    v-model="state.price_max"
                                    type="number"
                                    min="0"
                                    :placeholder="t('productsPage.max')"
                                    class="h-9 w-full min-w-0 rounded-lg border-border bg-background px-2.5 text-sm text-foreground placeholder:text-shop-light focus:border-primary focus:ring-primary"
                                />
                                <button type="submit" class="h-9 shrink-0 rounded-lg border border-border bg-muted px-3 text-sm font-bold hover:bg-muted/70">OK</button>
                            </form>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2.5 text-sm">
                            <input v-model="state.in_stock" type="checkbox" class="h-4 w-4 rounded border-border bg-background text-primary focus:ring-primary" />
                            {{ t('productsPage.inStockOnly') }}
                        </label>

                        <div v-if="availableLocations.length">
                            <h3 class="mb-2 text-sm font-bold">{{ t('productsPage.city') }}</h3>
                            <ul class="max-h-48 space-y-1.5 overflow-y-auto text-sm">
                                <li v-for="city in availableLocations" :key="city">
                                    <label class="flex cursor-pointer items-center gap-2.5 text-shop-muted">
                                        <input
                                            type="checkbox"
                                            :checked="state.locations.includes(city)"
                                            class="h-4 w-4 rounded border-border bg-background text-primary focus:ring-primary"
                                            @change="toggleLocation(city)"
                                        />
                                        {{ city }}
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>
                </aside>

                <!-- ── Résultats ── -->
                <section class="min-w-0 flex-1">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3 lg:mb-4 lg:rounded-xl lg:border lg:border-border lg:bg-card lg:px-4 lg:py-3">
                        <p class="text-sm text-shop-muted">
                            <span class="font-bold text-foreground">{{ t('productsPage.results', { count: total }) }}</span>
                            <template v-if="state.search"> {{ t('productsPage.for') }} <span class="font-bold text-primary">« {{ state.search }} »</span></template>
                            <template v-if="activeCategoryName"> · {{ activeCategoryName }}</template>
                        </p>
                        <label class="flex items-center gap-2 text-sm">
                            <span class="text-shop-muted">{{ t('productsPage.sortBy') }}</span>
                            <select
                                v-model="state.sort"
                                class="h-9 rounded-lg border-border bg-card py-0 pl-3 pr-8 text-sm text-foreground focus:border-primary focus:ring-primary"
                            >
                                <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>
                    </div>

                    <div v-if="loading" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                        <div v-for="index in 10" :key="index" class="h-80 animate-pulse rounded-xl bg-card" />
                    </div>
                    <div v-else-if="products.data?.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                        <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
                    </div>
                    <div v-else class="rounded-xl border border-dashed border-border bg-card p-10 text-center">
                        <p class="text-base font-semibold">{{ t('productsPage.empty') }}</p>
                        <button v-if="hasActiveFilters" type="button" class="mt-3 text-sm font-bold text-primary hover:underline" @click="resetFilters">
                            {{ t('productsPage.reset') }}
                        </button>
                    </div>

                    <nav v-if="products.links?.length > 3" class="mt-8 flex flex-wrap items-center justify-center gap-1.5">
                        <Link
                            v-for="(link, index) in products.links"
                            :key="`${link.label}-${index}`"
                            :href="link.url || '#'"
                            class="min-w-[38px] rounded-lg border px-3 py-2 text-center text-sm font-medium"
                            :class="[
                                link.active ? 'border-primary bg-primary text-white' : 'border-border bg-card text-foreground hover:bg-muted',
                                !link.url ? 'pointer-events-none opacity-40' : '',
                            ]"
                            preserve-scroll
                        >
                            <span v-html="link.label" />
                        </Link>
                    </nav>
                </section>
            </div>
        </main>

        <BottomNav />
    </div>
</template>
