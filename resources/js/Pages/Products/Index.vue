<script setup>
import ActiveFilters from '@/components/products/listing/ActiveFilters.vue';
import FiltersPanel from '@/components/products/listing/FiltersPanel.vue';
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import ProductsGrid from '@/components/products/listing/ProductsGrid.vue';
import ResultsCount from '@/components/products/listing/ResultsCount.vue';
import SearchBar from '@/components/products/listing/SearchBar.vue';
import SortDropdown from '@/components/products/listing/SortDropdown.vue';
import { Button } from '@/components/ui/button';
import { useFilters } from '@/composables/useFilters';
import { useSearch } from '@/composables/useSearch';
import { Head, Link, router } from '@inertiajs/vue3';
import { Filter } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, required: true },
    availableCategories: { type: Array, default: () => [] },
    availableLocations: { type: Array, default: () => [] },
});

const loading = ref(false);
const mobileCategoryDropdownOpen = ref(false);
const { t } = useI18n();

const state = reactive({
    search: props.filters.search || '',
    categories: [...(props.filters.categories || [])],
    locations: [...(props.filters.locations || [])],
    price_min: props.filters.price_min || '',
    price_max: props.filters.price_max || '',
    in_stock: Boolean(props.filters.in_stock),
    sort: props.filters.sort || 'newest',
});

const { hasActiveFilters, toQueryParams } = useFilters(state);
const { search, isSearching } = useSearch(() => applyFilters(), 500);

const activeFilterChips = computed(() => {
    const chips = [];
    const sortLabelMap = {
        newest: t('productsPage.sortNewest'),
        price_asc: t('productsPage.sortPriceAsc'),
        price_desc: t('productsPage.sortPriceDesc'),
        popular: t('productsPage.sortPopular'),
    };

    if (state.search) chips.push({ key: 'search', label: t('productsPage.searchChip', { value: state.search }) });
    if (state.in_stock) chips.push({ key: 'in_stock', label: t('productsPage.inStockChip') });
    if (state.price_min) chips.push({ key: 'price_min', label: t('productsPage.priceMinChip', { value: state.price_min }) });
    if (state.price_max) chips.push({ key: 'price_max', label: t('productsPage.priceMaxChip', { value: state.price_max }) });
    if (state.sort && state.sort !== 'newest') chips.push({ key: 'sort', label: t('productsPage.sortChip', { value: sortLabelMap[state.sort] || state.sort }) });

    state.categories.forEach((categoryId) => {
        const category = props.availableCategories.find((item) => item.id === categoryId);
        chips.push({ key: `category-${categoryId}`, type: 'category', value: categoryId, label: t('productsPage.categoryChip', { value: category?.name || categoryId }) });
    });

    state.locations.forEach((location) => {
        chips.push({ key: `location-${location}`, type: 'location', value: location, label: t('productsPage.cityChip', { value: location }) });
    });

    return chips;
});

const applyFilters = () => {
    loading.value = true;
    router.get(route('products.index'), toQueryParams(), {
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

watch(
    () => state.search,
    () => {
        search();
    },
);

watch(
    () => [state.sort, state.in_stock, state.price_min, state.price_max],
    () => {
        applyFilters();
    },
);

watch(
    () => state.categories,
    () => {
        applyFilters();
    },
    { deep: true },
);

watch(
    () => state.locations,
    () => {
        applyFilters();
    },
    { deep: true },
);

const toggleCategory = (categoryId) => {
    if (state.categories.includes(categoryId)) {
        state.categories = state.categories.filter((id) => id !== categoryId);
        return;
    }
    state.categories.push(categoryId);
};

const toggleLocation = (city) => {
    if (state.locations.includes(city)) {
        state.locations = state.locations.filter((item) => item !== city);
        return;
    }
    state.locations.push(city);
};

const clearSearch = () => {
    state.search = '';
};

const resetFilters = () => {
    state.search = '';
    state.categories = [];
    state.locations = [];
    state.price_min = '';
    state.price_max = '';
    state.in_stock = false;
    state.sort = 'newest';
    applyFilters();
};

const removeFilter = (filter) => {
    if (filter.key === 'search') state.search = '';
    if (filter.key === 'in_stock') state.in_stock = false;
    if (filter.key === 'price_min') state.price_min = '';
    if (filter.key === 'price_max') state.price_max = '';
    if (filter.key === 'sort') state.sort = 'newest';
    if (filter.type === 'category') state.categories = state.categories.filter((id) => id !== filter.value);
    if (filter.type === 'location') state.locations = state.locations.filter((city) => city !== filter.value);
};
</script>

<template>
    <Head :title="t('productsPage.headTitle')" />

    <div>
        <ProductsNavbar />
        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-3">
                    <h1 class="text-2xl font-bold">{{ t('productsPage.title') }}</h1>
                    <Link href="/" class="text-sm text-muted-foreground underline-offset-4 hover:underline">
                        {{ t('public.backHome') }}
                    </Link>
                </div>
                <SearchBar v-model="state.search" :is-searching="isSearching" @clear="clearSearch" />
            </div>

            <div class="flex items-center justify-between gap-4">
                <ResultsCount :total="products.total || 0" />
                <div class="flex items-center gap-2">
                    <SortDropdown v-model="state.sort" />
                    <div class="relative lg:hidden">
                        <Button type="button" variant="outline" class="gap-2" @click="mobileCategoryDropdownOpen = !mobileCategoryDropdownOpen">
                            <Filter class="h-4 w-4" />
                            {{ t('productsPage.filterCategories') }}
                        </Button>

                        <div
                            v-if="mobileCategoryDropdownOpen"
                            class="absolute right-0 top-12 z-50 w-64 rounded-lg border border-border bg-background p-3 shadow-lg"
                        >
                            <p class="mb-2 text-sm font-semibold">{{ t('productsPage.filterByCategories') }}</p>
                            <div class="max-h-64 space-y-2 overflow-y-auto">
                                <label
                                    v-for="category in availableCategories"
                                    :key="category.id"
                                    class="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground"
                                >
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-input"
                                        :checked="state.categories.includes(category.id)"
                                        @change="toggleCategory(category.id)"
                                    />
                                    <span>{{ category.name }}</span>
                                </label>
                            </div>

                            <div class="mt-3 space-y-2 border-t border-border pt-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ t('public.price') }}</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <input
                                        v-model="state.price_min"
                                        type="number"
                                        min="0"
                                        :placeholder="t('public.min')"
                                        class="h-9 rounded-md border border-input bg-background px-2 text-xs"
                                    />
                                    <input
                                        v-model="state.price_max"
                                        type="number"
                                        min="0"
                                        :placeholder="t('public.max')"
                                        class="h-9 rounded-md border border-input bg-background px-2 text-xs"
                                    />
                                </div>
                            </div>

                            <Button type="button" variant="outline" class="mt-3 w-full" @click="mobileCategoryDropdownOpen = false">
                                {{ t('public.close') }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <ActiveFilters
                :filters="activeFilterChips"
                @remove="removeFilter"
                @reset="resetFilters"
            />

            <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
                <aside class="hidden rounded-xl border border-border p-4 lg:block">
                    <FiltersPanel
                        :categories="availableCategories"
                        :locations="availableLocations"
                        :filters="state"
                        :has-active-filters="hasActiveFilters"
                        @toggle-category="toggleCategory"
                        @toggle-location="toggleLocation"
                        @reset="resetFilters"
                    />
                </aside>

                <section class="space-y-6">
                    <ProductsGrid :products="products.data || []" :loading="loading" />

                    <div v-if="products.links?.length > 3" class="flex flex-wrap items-center gap-2">
                        <Link
                            v-for="(link, index) in products.links"
                            :key="`${link.label}-${index}`"
                            :href="link.url || '#'"
                            class="rounded-md border px-3 py-1.5 text-sm"
                            :class="link.active ? 'border-primary bg-primary text-primary-foreground' : 'border-border text-muted-foreground'"
                            :disabled="!link.url"
                            preserve-scroll
                            preserve-state
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

