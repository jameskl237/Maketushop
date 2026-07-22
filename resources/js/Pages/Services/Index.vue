<script setup>
import ServiceCard from '@/components/services/ServiceCard.vue';
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import BottomNav from '@/components/shop/BottomNav.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useSearch } from '@/composables/useSearch';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, X } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    services: { type: Object, required: true },
    filters: { type: Object, required: true },
    availableCategories: { type: Array, default: () => [] },
    availableLocations: { type: Array, default: () => [] },
});

const loading = ref(false);
const { t } = useI18n();

const state = reactive({
    search: props.filters.search || '',
    categories: [...(props.filters.categories || [])],
    locations: [...(props.filters.locations || [])],
    price_min: props.filters.price_min || '',
    price_max: props.filters.price_max || '',
    rating_min: props.filters.rating_min || '',
    sort: props.filters.sort || 'newest',
});

const { search } = useSearch(() => applyFilters(), 500);

const categoryChips = computed(() => [
    { id: 'all', name: t('servicesPage.allCategories') },
    ...props.availableCategories,
]);

const ratingChips = computed(() => [
    { value: '', label: t('servicesPage.ratingAll') },
    { value: '4', label: t('servicesPage.rating4Plus') },
    { value: '3', label: t('servicesPage.rating3Plus') },
]);

const toQueryParams = () => ({
    search: state.search || undefined,
    categories: state.categories.length ? state.categories : undefined,
    locations: state.locations.length ? state.locations : undefined,
    price_min: state.price_min || undefined,
    price_max: state.price_max || undefined,
    rating_min: state.rating_min || undefined,
    sort: state.sort || 'newest',
});

const applyFilters = () => {
    loading.value = true;
    router.get(route('services.index'), toQueryParams(), {
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

watch(() => state.search, () => search());
watch(() => state.categories, () => applyFilters(), { deep: true });
watch(() => state.rating_min, () => applyFilters());

const toggleCategory = (categoryId) => {
    if (categoryId === 'all') {
        state.categories = [];
        return;
    }
    if (state.categories.includes(categoryId)) {
        state.categories = state.categories.filter((id) => id !== categoryId);
        return;
    }
    state.categories = [categoryId];
};

const clearSearch = () => {
    state.search = '';
};
</script>

<template>
    <Head :title="t('servicesPage.headTitle')" />

    <div class="min-h-screen bg-shop-bg pb-16 text-foreground">
        <ProductsNavbar />

        <Transition name="page-fade" mode="out-in">
            <main class="mx-auto max-w-7xl space-y-4 py-3">
                <section class="space-y-3 px-3">
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-shop-light">{{ t('servicesPage.subtitle') }}</p>
                            <h1 class="font-display text-[22px] font-extrabold leading-tight">{{ t('servicesPage.title') }}</h1>
                        </div>
                        <Link :href="route('products.index')" class="text-[11px] font-bold text-primary">{{ t('public.products') }}</Link>
                    </div>

                    <div class="relative">
                        <Search class="pointer-events-none absolute left-[11px] top-1/2 h-4 w-4 -translate-y-1/2 text-shop-light" />
                        <Input
                            v-model="state.search"
                            type="search"
                            class="h-[38px] rounded-[12px] border-[1.5px] border-border bg-shop-bg pl-9 pr-9 text-[12px] shadow-none placeholder:text-shop-light"
                            :placeholder="t('servicesPage.searchPlaceholder')"
                        />
                        <Button
                            v-if="state.search"
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="absolute right-1 top-1/2 h-7 w-7 -translate-y-1/2 rounded-[9px]"
                            @click="clearSearch"
                        >
                            <X class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </section>

                <ScrollArea class="px-3">
                    <div class="flex min-w-max gap-2">
                        <Button
                            v-for="category in categoryChips"
                            :key="category.id"
                            type="button"
                            variant="outline"
                            class="h-7 rounded-[20px] px-3 text-[11.5px] font-medium shadow-none"
                            :class="(category.id === 'all' && !state.categories.length) || state.categories.includes(category.id)
                                ? 'border-primary bg-primary text-white'
                                : 'border-border bg-white text-shop-muted'"
                            @click="toggleCategory(category.id)"
                        >
                            {{ category.name }}
                        </Button>
                    </div>
                </ScrollArea>

                <ScrollArea class="px-3">
                    <div class="flex min-w-max gap-2">
                        <Button
                            v-for="chip in ratingChips"
                            :key="chip.value"
                            type="button"
                            variant="outline"
                            class="h-7 rounded-[20px] px-3 text-[11.5px] font-medium shadow-none"
                            :class="state.rating_min === chip.value
                                ? 'border-orange bg-orange text-white'
                                : 'border-border bg-white text-shop-muted'"
                            @click="state.rating_min = chip.value"
                        >
                            {{ chip.label }}
                        </Button>
                    </div>
                </ScrollArea>

                <section class="px-3">
                    <div v-if="loading" class="grid grid-cols-2 gap-2.5">
                        <div v-for="index in 6" :key="index" class="h-60 animate-pulse rounded-[16px] bg-white" />
                    </div>
                    <div v-else-if="services.data?.length" class="grid grid-cols-2 gap-2.5">
                        <ServiceCard v-for="service in services.data" :key="service.id" :service="service" />
                    </div>
                    <div v-else class="rounded-[16px] border border-dashed border-border bg-white p-8 text-center text-sm text-shop-muted">
                        {{ t('servicesPage.emptyDescription') }}
                    </div>

                    <div v-if="services.links?.length > 3" class="mt-4 flex flex-wrap items-center justify-center gap-2">
                        <Link
                            v-for="(link, index) in services.links"
                            :key="`${link.label}-${index}`"
                            :href="link.url || '#'"
                            class="rounded-[10px] border px-3 py-1.5 text-xs"
                            :class="link.active ? 'border-primary bg-primary text-white' : 'border-border bg-white text-shop-muted'"
                            preserve-scroll
                            preserve-state
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </section>
            </main>
        </Transition>

        <BottomNav />
    </div>
</template>
