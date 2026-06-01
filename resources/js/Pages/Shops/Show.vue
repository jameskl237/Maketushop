<script setup>
import ProductCard from '@/components/products/listing/ProductCard.vue';
import BottomNav from '@/components/shop/BottomNav.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useSearch } from '@/composables/useSearch';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Check, MessageCircle, Search, Star } from 'lucide-vue-next';
import CopyShopLinkButton from '@/components/supplier/CopyShopLinkButton.vue';
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    shop: { type: Object, required: true },
    products: { type: Object, required: true },
    filters: { type: Object, required: true },
    availableCategories: { type: Array, default: () => [] },
});

const loading = ref(false);
const { t } = useI18n();

const state = reactive({
    search: props.filters.search || '',
    categories: [...(props.filters.categories || [])],
});

const { search } = useSearch(() => applyFilters(), 500);

const initials = computed(() =>
    String(props.shop.name || 'MS')
        .split(' ')
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase(),
);

const categoryChips = computed(() => [{ id: 'all', name: 'Tous' }, ...props.availableCategories]);

const whatsappUrl = computed(() => {
    const digits = String(props.shop.phone || '').replace(/[^\d]/g, '');
    if (!digits) return null;
    const phone = digits.startsWith('0') ? `237${digits.replace(/^0+/, '')}` : digits;
    const text = encodeURIComponent(`Bonjour ${props.shop.name}, je souhaite en savoir plus sur vos produits.`);
    return `https://wa.me/${phone}?text=${text}`;
});

const stats = computed(() => [
    { label: 'Produits', value: props.shop.products_count || 0 },
    { label: 'Note', value: props.shop.rating || '4.8' },
    { label: 'Avis', value: props.shop.reviews_count || 0 },
    { label: 'Ville', value: props.shop.city || '-' },
]);

const applyFilters = () => {
    loading.value = true;
    router.get(
        route('shops.show', { shop: props.shop.id }),
        {
            search: state.search || undefined,
            categories: state.categories.length ? state.categories : undefined,
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

watch(() => state.search, () => search());
watch(() => state.categories, () => applyFilters(), { deep: true });

const toggleCategory = (categoryId) => {
    if (categoryId === 'all') {
        state.categories = [];
        return;
    }
    state.categories = state.categories.includes(categoryId) ? [] : [categoryId];
};

const shareShop = async () => {
    const url = window.location.href;
    if (navigator.share) {
        await navigator.share({ title: props.shop.name, url });
        return;
    }
    await navigator.clipboard?.writeText(url);
};
</script>

<template>
    <Head :title="`${shop.name} - Boutique`" />

    <div class="min-h-screen bg-shop-bg pb-16 text-foreground">
        <Transition name="page-fade" mode="out-in">
            <main class="mx-auto max-w-3xl">
                <section class="relative overflow-hidden bg-gradient-to-br from-primary to-orange px-4 pb-9 pt-4 text-white">
                    <!-- Bannière de la boutique (si configurée) -->
                    <template v-if="shop.banner_image">
                        <img :src="shop.banner_image" :alt="shop.name" class="absolute inset-0 h-full w-full object-cover" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-black/20"></div>
                    </template>

                    <div class="relative mb-5 flex items-center justify-between">
                        <Link :href="route('shops.index')">
                            <Button variant="ghost" size="icon" class="h-8 w-8 rounded-[10px] bg-white/20 text-white shadow-none">
                                <ArrowLeft class="h-4 w-4" />
                            </Button>
                        </Link>
                        <CopyShopLinkButton :shop-id="shop.id" :shop-name="shop.name" />
                    </div>

                    <div class="relative flex items-end gap-3">
                        <div class="relative">
                            <Avatar class="h-14 w-14 rounded-[18px] bg-white">
                                <AvatarImage :src="shop.logo_url || shop.logo" :alt="shop.name" />
                                <AvatarFallback class="rounded-[18px] bg-white font-display text-lg font-extrabold text-primary">
                                    {{ initials }}
                                </AvatarFallback>
                            </Avatar>
                            <Badge v-if="shop.verified" class="absolute -bottom-1 -right-1 h-5 w-5 rounded-full bg-shop-green p-0">
                                <Check class="h-3 w-3" />
                            </Badge>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h1 class="line-clamp-1 font-display text-[22px] font-extrabold leading-tight">{{ shop.name }}</h1>
                            <p class="mt-1 text-[12px] text-white/80">{{ shop.city }}<span v-if="shop.district">, {{ shop.district }}</span></p>
                        </div>
                    </div>
                    <p class="mt-3 line-clamp-2 text-[12px] leading-5 text-white/85">
                        {{ shop.description || t('shopShow.aboutFallback') }}
                    </p>
                </section>

                <section class="relative z-[1] mx-3 -mt-4 rounded-[16px] bg-white p-3 shadow-[0_8px_24px_rgba(15,10,30,0.08)]">
                    <div class="grid grid-cols-4 gap-1 text-center">
                        <div v-for="item in stats" :key="item.label">
                            <p class="line-clamp-1 font-display text-[14px] font-extrabold text-foreground">{{ item.value }}</p>
                            <p class="mt-0.5 text-[9.5px] text-shop-light">{{ item.label }}</p>
                        </div>
                    </div>
                </section>

                <section class="space-y-3 px-3 py-4">
                    <a v-if="whatsappUrl" :href="whatsappUrl" target="_blank" rel="noopener noreferrer">
                        <Button class="h-10 w-full rounded-[14px] bg-gradient-to-r from-primary to-pink text-[12px] font-bold text-white shadow-none">
                            <MessageCircle class="h-4 w-4" />
                            {{ t('shopShow.contact') }}
                        </Button>
                    </a>

                    <div class="relative">
                        <Search class="pointer-events-none absolute left-[11px] top-1/2 h-4 w-4 -translate-y-1/2 text-shop-light" />
                        <Input
                            v-model="state.search"
                            type="search"
                            class="h-[38px] rounded-[12px] border-[1.5px] border-border bg-white pl-9 text-[12px] shadow-none placeholder:text-shop-light"
                            :placeholder="t('shopShow.searchPlaceholder')"
                        />
                    </div>

                    <ScrollArea>
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

                    <div v-if="loading" class="grid grid-cols-2 gap-2.5">
                        <div v-for="index in 4" :key="index" class="h-60 animate-pulse rounded-[16px] bg-white" />
                    </div>
                    <div v-else-if="products.data?.length" class="grid grid-cols-2 gap-2.5">
                        <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
                    </div>
                    <div v-else class="rounded-[16px] border border-dashed border-border bg-white p-8 text-center text-sm text-shop-muted">
                        {{ t('shopShow.emptyProducts') }}
                    </div>
                </section>
            </main>
        </Transition>

        <BottomNav />
    </div>
</template>
