<script setup>
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import BottomNav from '@/components/shop/BottomNav.vue';
import ShopCard from '@/components/shop/ShopCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    shops: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
});

const search = ref(props.filters?.search || '');
const { t } = useI18n();

let timeoutId = null;
watch(search, (value) => {
    if (timeoutId) clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('shops.index'), { search: value || undefined }, { preserveState: true, replace: true });
    }, 400);
});

const hasShops = computed(() => Boolean(props.shops.data?.length));
</script>

<template>
    <Head :title="t('shopsPage.headTitle')" />

    <div class="min-h-screen bg-shop-bg pb-16 text-foreground">
        <ProductsNavbar />

        <Transition name="page-fade" mode="out-in">
            <main class="mx-auto max-w-3xl space-y-4 px-3 py-3">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-shop-light">{{ t('shopsPage.sellers') }}</p>
                        <h1 class="font-display text-[22px] font-extrabold leading-tight">{{ t('shopsPage.title') }}</h1>
                    </div>
                    <Link :href="route('products.index')" class="text-[11px] font-bold text-primary">Produits</Link>
                </div>

                <div class="relative">
                    <Search class="pointer-events-none absolute left-[11px] top-1/2 h-4 w-4 -translate-y-1/2 text-shop-light" />
                    <Input
                        v-model="search"
                        type="search"
                        class="h-[38px] rounded-[12px] border-[1.5px] border-border bg-shop-bg pl-9 pr-9 text-[12px] shadow-none placeholder:text-shop-light"
                        placeholder="Rechercher une boutique..."
                    />
                    <Button
                        v-if="search"
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="absolute right-1 top-1/2 h-7 w-7 -translate-y-1/2 rounded-[9px]"
                        @click="search = ''"
                    >
                        <X class="h-3.5 w-3.5" />
                    </Button>
                </div>

                <div v-if="!hasShops" class="rounded-[16px] border border-dashed border-border bg-white p-8 text-center text-sm text-shop-muted">
                    {{ t('shopsPage.empty') }}
                </div>

                <div v-else class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    <ShopCard v-for="shop in shops.data" :key="shop.id" :shop="shop" />
                </div>
            </main>
        </Transition>

        <BottomNav />
    </div>
</template>
