<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import ShopsGrid from '@/components/supplier/ShopsGrid.vue';
import StatsOverview from '@/components/supplier/StatsOverview.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Package, ShoppingCart, Store } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    shops: {
        type: Array,
        default: () => [],
    },
    publishedProductsCount: {
        type: Number,
        default: 0,
    },
});

const createShopDialogOpen = ref(false);
const loading = ref(false);
const canCreateShop = computed(() => props.shops.length === 0);
const { t } = useI18n();

const form = useForm({
    name: '',
    description: '',
    city: '',
    district: '',
});

const stats = computed(() => [
    {
        title: t('supplier.stats.activeShops'),
        value: props.shops.length,
        hint: props.shops.length > 0 ? t('supplier.stats.liveTracking') : t('supplier.stats.noShop'),
        icon: Store,
        trendType: props.shops.length > 0 ? 'positive' : 'neutral',
    },
    {
        title: t('supplier.stats.publishedProducts'),
        value: props.publishedProductsCount,
        hint: props.publishedProductsCount > 0 ? t('supplier.stats.linkedProducts') : t('supplier.stats.noProduct'),
        icon: Package,
        trendType: props.publishedProductsCount > 0 ? 'positive' : 'neutral',
    },
    {
        title: t('supplier.stats.orders'),
        value: '-',
        hint: t('supplier.stats.pendingZero'),
        icon: ShoppingCart,
        trendType: 'neutral',
    },
    // {
    //     title: 'Revenus',
    //     value: '45 230 FCFA',
    //     hint: '+18% ce mois',
    //     icon: TrendingUp,
    //     trendType: 'positive',
    // },
]);

const openCreateDialog = () => {
    if (!canCreateShop.value) return;
    createShopDialogOpen.value = true;
};

const submitShop = () => {
    form.post(route('backoffice.supplier.shops.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createShopDialogOpen.value = false;
            form.reset();
        },
    });
};

const goToShopProducts = (shop) => {
    router.visit(route('backoffice.supplier.shops.show', { shop: shop.id }));
};
</script>

<template>
    <!-- Exemple: page Inertia principale du dashboard supplier -->
    <Head :title="$t('supplier.dashboardTitle')" />

    <SupplierLayout
        :title="$t('supplier.spaceTitle')"
        :subtitle="$t('supplier.spaceSubtitle')"
        active-route="backoffice.supplier.dashboard"
        :can-create-shop="canCreateShop"
        @create-shop="openCreateDialog"
    >
        <template #content>
            <StatsOverview :stats="stats" :loading="loading" />

            <ShopsGrid
                :shops="shops"
                :loading="loading"
                :can-create-shop="canCreateShop"
                @create-shop="openCreateDialog"
                @view-shop="goToShopProducts"
            />
        </template>
    </SupplierLayout>

    <Dialog :open="createShopDialogOpen" @update:open="createShopDialogOpen = $event">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ $t('supplier.createShopTitle') }}</DialogTitle>
                <DialogDescription>
                    {{ $t('supplier.createShopDescription') }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submitShop">
                <div class="space-y-2">
                    <Label for="shop-name">{{ $t('supplier.shopName') }}</Label>
                    <Input id="shop-name" v-model="form.name" type="text" required />
                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="shop-city">{{ $t('supplier.city') }}</Label>
                        <Input id="shop-city" v-model="form.city" type="text" required />
                        <p v-if="form.errors.city" class="text-sm text-destructive">{{ form.errors.city }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="shop-district">{{ $t('supplier.district') }}</Label>
                        <Input id="shop-district" v-model="form.district" type="text" required />
                        <p v-if="form.errors.district" class="text-sm text-destructive">{{ form.errors.district }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="shop-description">{{ $t('common.description') }}</Label>
                    <Textarea id="shop-description" v-model="form.description" rows="4" />
                    <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button type="button" variant="outline" @click="createShopDialogOpen = false">
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? $t('common.saving') : $t('supplier.createShop') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

