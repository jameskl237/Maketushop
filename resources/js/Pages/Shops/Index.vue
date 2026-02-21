<script setup>
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, Store } from 'lucide-vue-next';
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
        router.get(
            route('shops.index'),
            { search: value || undefined },
            { preserveState: true, replace: true },
        );
    }, 400);
});

const hasShops = computed(() => Boolean(props.shops.data?.length));
</script>

<template>
    <Head :title="t('shopsPage.headTitle')" />

    <div>
        <ProductsNavbar />
        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <Link href="/" class="hover:text-foreground">{{ t('public.home') }}</Link>
                <span>/</span>
                <Link :href="route('products.index')" class="hover:text-foreground">{{ t('public.products') }}</Link>
                <span>/</span>
                <span class="text-foreground">{{ t('public.shops') }}</span>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-bold">{{ t('shopsPage.title') }}</h1>
                <div class="relative w-full sm:w-80">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="pl-9" :placeholder="t('shopsPage.searchPlaceholder')" />
                </div>
            </div>

            <div v-if="!hasShops" class="rounded-xl border border-dashed border-border p-10 text-center text-muted-foreground">
                {{ t('shopsPage.empty') }}
            </div>

            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="shop in shops.data" :key="shop.id" class="rounded-xl border">
                    <CardHeader class="space-y-2">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border border-border bg-muted">
                                <img
                                    v-if="shop.logo_url"
                                    :src="shop.logo_url"
                                    :alt="`Logo ${shop.name}`"
                                    class="h-full w-full object-cover"
                                />
                                <Store v-else class="h-5 w-5 text-muted-foreground" />
                            </div>
                            <CardTitle class="line-clamp-1 text-lg">{{ shop.name }}</CardTitle>
                        </div>
                        <Badge variant="secondary">{{ t('shopsPage.productsCount', { count: shop.products_count }) }}</Badge>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <p class="text-sm text-muted-foreground">{{ shop.city }} - {{ shop.district }}</p>
                        <p class="line-clamp-2 text-sm text-muted-foreground">
                            {{ shop.description || t('shopsPage.noDescription') }}
                        </p>
                        <Link :href="route('shops.show', { shop: shop.id })">
                            <Button variant="outline" class="w-full">{{ t('shopsPage.viewShop') }}</Button>
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>

