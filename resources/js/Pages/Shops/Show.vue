<script setup>
import ProductCard from '@/components/products/listing/ProductCard.vue';
import ProductCardSkeleton from '@/components/products/listing/ProductCardSkeleton.vue';
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useSearch } from '@/composables/useSearch';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Check,
    Clock,
    Copy,
    Filter,
    Heart,
    Loader2,
    MapPin,
    MessageCircle,
    Package,
    Search,
    Share2,
    Star,
    Store,
    User,
    X,
    Info,
} from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    shop: { type: Object, required: true },
    products: { type: Object, required: true },
    filters: { type: Object, required: true },
    availableCategories: { type: Array, default: () => [] },
});

const activeTab = ref('products');
const loading = ref(false);
const mobileFiltersOpen = ref(false);
const copiedShopLink = ref(false);
let copiedShopLinkTimeout = null;
const { t } = useI18n();
const productionBaseUrl = (import.meta.env.VITE_PUBLIC_APP_URL || 'https://maketushop.com').replace(/\/+$/, '');

const state = reactive({
    search: props.filters.search || '',
    categories: [...(props.filters.categories || [])],
    price_min: props.filters.price_min || '',
    price_max: props.filters.price_max || '',
    in_stock: Boolean(props.filters.in_stock),
});

const { search, isSearching } = useSearch(() => applyFilters(), 500);

const applyFilters = () => {
    loading.value = true;
    router.get(
        route('shops.show', { shop: props.shop.id }),
        {
            search: state.search || undefined,
            categories: state.categories.length ? state.categories : undefined,
            price_min: state.price_min || undefined,
            price_max: state.price_max || undefined,
            in_stock: state.in_stock ? 1 : undefined,
        },
        {
            preserveState: true,
            replace: true,
            onFinish: () => {
                loading.value = false;
            },
        },
    );
};

watch(
    () => state.search,
    () => search(),
);

watch(
    () => [state.price_min, state.price_max, state.in_stock],
    () => applyFilters(),
);

watch(
    () => state.categories,
    () => applyFilters(),
    { deep: true },
);

const toggleCategory = (categoryId) => {
    if (state.categories.includes(categoryId)) {
        state.categories = state.categories.filter((id) => id !== categoryId);
        return;
    }
    state.categories.push(categoryId);
};

const clearSearch = () => {
    state.search = '';
};

const clearAllFilters = () => {
    state.search = '';
    state.categories = [];
    state.price_min = '';
    state.price_max = '';
    state.in_stock = false;
    applyFilters();
};

const whatsappUrl = computed(() => {
    const raw = String(props.shop.phone || '');
    const digits = raw.replace(/[^\d]/g, '');
    if (!digits) return null;
    const phone = digits.startsWith('0') ? `237${digits.replace(/^0+/, '')}` : digits;
    const text = encodeURIComponent(`Bonjour ${props.shop.name}, je souhaite en savoir plus sur vos produits.`);
    return `https://wa.me/${phone}?text=${text}`;
});

const slugify = (value) =>
    String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');

const shopPublicUrl = computed(() =>
    `${productionBaseUrl}${route('shops.show', { shop: props.shop.id, slug: slugify(props.shop.name) || 'boutique' }, false)}`,
);

const copyShopLink = async () => {
    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(shopPublicUrl.value);
        } else {
            const input = document.createElement('textarea');
            input.value = shopPublicUrl.value;
            input.setAttribute('readonly', '');
            input.style.position = 'absolute';
            input.style.left = '-9999px';
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
        }
        copiedShopLink.value = true;
        if (copiedShopLinkTimeout) clearTimeout(copiedShopLinkTimeout);
        copiedShopLinkTimeout = setTimeout(() => {
            copiedShopLink.value = false;
        }, 1800);
    } catch (error) {
        copiedShopLink.value = false;
    }
};
</script>

<template>
    <Head :title="`${shop.name} - Boutique`" />

    <div>
        <ProductsNavbar />

        <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <Link href="/">
                <Button variant="outline">{{ t('public.backHome') }}</Button>
            </Link>
            <Link :href="route('login')">
                <Button variant="outline" size="icon" :aria-label="t('auth.login')">
                    <User class="h-4 w-4" />
                </Button>
            </Link>
        </div>

        <section class="relative h-64 overflow-hidden md:h-80">
            <img v-if="shop.banner_image" :src="shop.banner_image" :alt="shop.name" class="h-full w-full object-cover" />
            <div v-else class="absolute inset-0 bg-gradient-to-br from-primary/20 via-primary/10 to-secondary/20" />

            <div class="absolute inset-0 bg-gradient-to-b from-black/20 via-black/40 to-black/60" />
            <div
                class="absolute inset-0 opacity-70"
                style="background-image: linear-gradient(to right, rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 40px 40px;"
            />

            <div class="relative mx-auto h-full max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-full flex-col items-center justify-center pb-8 text-center">
                    <Badge class="mb-4 bg-background/90 text-foreground backdrop-blur">
                        <Check class="mr-1 h-3 w-3 text-green-600" />
                        {{ t('shopShow.verified') }}
                    </Badge>
                    <h1 class="max-w-4xl text-4xl font-bold text-white drop-shadow-lg md:text-6xl">{{ shop.name }}</h1>
                    <p v-if="shop.tagline" class="mt-3 max-w-2xl text-lg text-white/90 md:text-xl">{{ shop.tagline }}</p>
                    <div class="mt-4 flex items-center gap-2 rounded-full bg-black/25 px-3 py-1.5 text-white/90 backdrop-blur-sm">
                        <MapPin class="h-4 w-4" />
                        <span>{{ shop.city }}, {{ shop.district }}</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="relative">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="relative -mt-16 mb-8 flex justify-center">
                    <div class="relative h-32 w-32 transition-transform duration-300 hover:scale-105">
                        <div class="absolute inset-0 overflow-hidden rounded-full border-4 border-background bg-card shadow-2xl">
                            <img v-if="shop.logo" :src="shop.logo" :alt="shop.name" class="h-full w-full object-cover" />
                            <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary/20 to-primary/5">
                                <Store class="h-16 w-16 text-primary" />
                            </div>
                        </div>

                        <div
                            v-if="shop.verified"
                            class="absolute -bottom-1 -right-1 z-10 flex h-9 w-9 items-center justify-center rounded-full border-2 border-background bg-green-500 shadow-lg"
                        >
                            <Check class="h-4 w-4 text-white" />
                        </div>
                    </div>
                </div>

                <div class="mb-6 flex flex-col gap-4 rounded-xl border border-border bg-card p-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="grid flex-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="flex items-center gap-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                <Package class="h-5 w-5 text-primary" />
                            </div>
                            <div>
                                <div class="text-sm text-muted-foreground">{{ t('shopShow.products') }}</div>
                                <div class="text-lg font-bold">{{ shop.products_count }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-500/10">
                                <Star class="h-5 w-5 text-yellow-500" />
                            </div>
                            <div>
                                <div class="text-sm text-muted-foreground">{{ t('shopShow.rating') }}</div>
                                <div class="text-lg font-bold">{{ shop.rating }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500/10">
                                <MapPin class="h-5 w-5 text-blue-500" />
                            </div>
                            <div>
                                <div class="text-sm text-muted-foreground">{{ t('shopShow.city') }}</div>
                                <div class="text-lg font-bold">{{ shop.city }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-500/10">
                                <Clock class="h-5 w-5 text-green-500" />
                            </div>
                            <div>
                                <div class="text-sm text-muted-foreground">{{ t('shopShow.responseTime') }}</div>
                                <div class="text-lg font-bold">{{ shop.response_time }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            v-if="whatsappUrl"
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Button>
                                <MessageCircle class="mr-2 h-4 w-4" />
                                {{ t('shopShow.contact') }}
                            </Button>
                        </a>
                        <Button v-else variant="outline" disabled>
                            <MessageCircle class="mr-2 h-4 w-4" />
                            {{ t('shopShow.contact') }}
                        </Button>
                        <Button variant="outline" size="icon"><Heart class="h-4 w-4" /></Button>
                        <Button variant="outline" size="icon"><Share2 class="h-4 w-4" /></Button>
                    </div>
                </div>

                <div class="mb-6 rounded-xl border border-border bg-card p-4">
                    <p class="mb-2 text-sm font-medium">{{ t('shopShow.copyLinkTitle') }}</p>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <Input :model-value="shopPublicUrl" readonly class="font-mono text-xs sm:text-sm" />
                        <Button class="gap-2 sm:w-auto" @click="copyShopLink">
                            <Copy class="h-4 w-4" />
                            {{ copiedShopLink ? t('public.copied') : t('shopShow.copyLinkButton') }}
                        </Button>
                    </div>
                </div>

                <div class="mb-6 flex flex-wrap items-center gap-2 border-b">
                    <Button
                        type="button"
                        variant="ghost"
                        class="rounded-none border-b-2 px-3"
                        :class="activeTab === 'products' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground'"
                        @click="activeTab = 'products'"
                    >
                        <Package class="mr-2 h-4 w-4" />
                        {{ t('shopShow.tabProducts', { count: shop.products_count }) }}
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        class="rounded-none border-b-2 px-3"
                        :class="activeTab === 'about' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground'"
                        @click="activeTab = 'about'"
                    >
                        <Info class="mr-2 h-4 w-4" />
                        {{ t('shopShow.tabAbout') }}
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        class="rounded-none border-b-2 px-3"
                        :class="activeTab === 'reviews' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground'"
                        @click="activeTab = 'reviews'"
                    >
                        <Star class="mr-2 h-4 w-4" />
                        {{ t('shopShow.tabReviews', { count: shop.reviews_count }) }}
                    </Button>
                </div>

                <div v-if="activeTab === 'products'" class="space-y-6 pb-8">
                    <div class="space-y-3">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="state.search"
                                type="search"
                                :placeholder="t('shopShow.searchPlaceholder')"
                                class="h-12 pl-10 pr-12"
                            />
                            <Button
                                v-if="state.search"
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="absolute right-2 top-1/2 h-8 w-8 -translate-y-1/2"
                                @click="clearSearch"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                            <Loader2
                                v-else-if="isSearching"
                                class="absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 animate-spin text-muted-foreground"
                            />
                        </div>

                        <div class="relative lg:hidden">
                            <Button
                                type="button"
                                variant="outline"
                                class="gap-2"
                                @click="mobileFiltersOpen = !mobileFiltersOpen"
                            >
                                <Filter class="h-4 w-4" />
                                {{ t('shopShow.filters') }}
                            </Button>

                            <div
                                v-if="mobileFiltersOpen"
                                class="absolute left-0 top-12 z-30 w-full rounded-lg border border-border bg-background p-3 shadow-lg"
                            >
                                <p class="mb-2 text-sm font-semibold">{{ t('shopShow.categories') }}</p>
                                <div class="max-h-48 space-y-2 overflow-y-auto">
                                    <label
                                        v-for="cat in availableCategories"
                                        :key="cat.id"
                                        class="flex cursor-pointer items-center gap-2 text-sm"
                                    >
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-input"
                                            :checked="state.categories.includes(cat.id)"
                                            @change="toggleCategory(cat.id)"
                                        />
                                        <span class="flex-1">{{ cat.name }}</span>
                                        <Badge variant="secondary">{{ cat.products_count }}</Badge>
                                    </label>
                                </div>

                                <div class="mt-3 space-y-2 border-t border-border pt-3">
                                    <p class="text-sm font-semibold">{{ t('public.price') }}</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <Input v-model="state.price_min" type="number" min="0" :placeholder="t('public.min')" />
                                        <Input v-model="state.price_max" type="number" min="0" :placeholder="t('public.max')" />
                                    </div>
                                </div>

                                <div class="mt-3 flex gap-2">
                                    <Button type="button" variant="outline" class="flex-1" @click="clearAllFilters">
                                        {{ t('public.reset') }}
                                    </Button>
                                    <Button type="button" class="flex-1" @click="mobileFiltersOpen = false">
                                        {{ t('public.close') }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-[250px_1fr]">
                        <aside class="hidden lg:block">
                            <div class="sticky top-24">
                                <Card class="space-y-4 p-6">
                                    <h3 class="font-semibold">{{ t('shopShow.filters') }}</h3>
                                    <div class="space-y-3 border-t pt-4">
                                        <h4 class="text-sm font-semibold">{{ t('shopShow.categories') }}</h4>
                                        <div class="max-h-[250px] space-y-2 overflow-y-auto pr-1">
                                            <label
                                                v-for="cat in availableCategories"
                                                :key="cat.id"
                                                class="flex cursor-pointer items-center gap-2 text-sm"
                                            >
                                                <input
                                                    type="checkbox"
                                                    class="h-4 w-4 rounded border-input"
                                                    :checked="state.categories.includes(cat.id)"
                                                    @change="toggleCategory(cat.id)"
                                                />
                                                <span class="flex-1">{{ cat.name }}</span>
                                                <Badge variant="secondary">{{ cat.products_count }}</Badge>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="space-y-3 border-t pt-4">
                                        <h4 class="text-sm font-semibold">{{ t('public.price') }}</h4>
                                        <div class="flex gap-2">
                                            <Input v-model="state.price_min" type="number" min="0" :placeholder="t('public.min')" />
                                            <Input v-model="state.price_max" type="number" min="0" :placeholder="t('public.max')" />
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between border-t pt-4 text-sm">
                                        <span>{{ t('public.inStockOnly') }}</span>
                                        <input v-model="state.in_stock" type="checkbox" class="h-4 w-4 rounded border-input" />
                                    </div>

                                    <Button variant="outline" class="w-full" @click="clearAllFilters">
                                        {{ t('public.reset') }}
                                    </Button>
                                </Card>
                            </div>
                        </aside>

                        <div class="space-y-6">
                            <div v-if="loading" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                <ProductCardSkeleton v-for="i in 9" :key="i" />
                            </div>

                            <div v-else-if="products.data?.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
                            </div>

                            <div
                                v-else
                                class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
                            >
                                {{ t('shopShow.emptyProducts') }}
                            </div>

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
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'about'" class="pb-8">
                    <Card>
                        <CardContent class="space-y-3 p-6 text-sm text-muted-foreground">
                            <p>{{ shop.description || t('shopShow.aboutFallback') }}</p>
                            <p>{{ t('shopShow.location') }} : {{ shop.city }}, {{ shop.district }}</p>
                        </CardContent>
                    </Card>
                </div>

                <div v-else class="pb-8">
                    <Card>
                        <CardContent class="p-6 text-sm text-muted-foreground">
                            {{ t('shopShow.reviewsSoon') }}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </div>
</template>

