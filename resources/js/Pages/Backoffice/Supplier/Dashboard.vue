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
const editShopDialogOpen = ref(false);
const loading = ref(false);
const canCreateShop = computed(() => props.shops.length === 0);
const { t } = useI18n();
const shopToEdit = ref(null);

const form = useForm({
    name: '',
    description: '',
    city: '',
    district: '',
    logo: null,
});

const editForm = useForm({
    name: '',
    description: '',
    city: '',
    district: '',
    phone: '',
    logo: null,
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
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            createShopDialogOpen.value = false;
            form.reset();
        },
    });
};

const onLogoChange = (event) => {
    form.logo = event.target.files?.[0] ?? null;
};

const goToShopProducts = (shop) => {
    router.visit(route('backoffice.supplier.shops.show', { shop: shop.id }));
};

const openEditDialog = (shop) => {
    shopToEdit.value = shop;
    editForm.reset();
    editForm.name = shop.name ?? '';
    editForm.description = shop.description ?? '';
    editForm.city = shop.city ?? '';
    editForm.district = shop.district ?? '';
    editForm.phone = shop.phone ?? '';
    editForm.logo = null;
    editShopDialogOpen.value = true;
};

const onEditLogoChange = (event) => {
    editForm.logo = event.target.files?.[0] ?? null;
};

const submitShopUpdate = () => {
    if (!shopToEdit.value) return;

    editForm
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(route('backoffice.supplier.shops.update', { shop: shopToEdit.value.id }), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                editShopDialogOpen.value = false;
                shopToEdit.value = null;
                editForm.reset();
            },
            onFinish: () => {
                editForm.transform((data) => data);
            },
        });
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
                @edit-shop="openEditDialog"
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

                <div class="space-y-2">
                    <Label for="shop-logo">Logo de la boutique</Label>
                    <Input id="shop-logo" type="file" accept="image/*" @change="onLogoChange" />
                    <p class="text-xs text-muted-foreground">Format image, max 5MB.</p>
                    <p v-if="form.errors.logo" class="text-sm text-destructive">{{ form.errors.logo }}</p>
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

    <Dialog :open="editShopDialogOpen" @update:open="editShopDialogOpen = $event">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Modifier la boutique</DialogTitle>
                <DialogDescription>
                    Mets a jour les informations de ta boutique. Laisse le telephone vide pour utiliser celui de ton profil.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submitShopUpdate">
                <div class="space-y-2">
                    <Label for="edit-shop-name">{{ $t('supplier.shopName') }}</Label>
                    <Input id="edit-shop-name" v-model="editForm.name" type="text" required />
                    <p v-if="editForm.errors.name" class="text-sm text-destructive">{{ editForm.errors.name }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="edit-shop-city">{{ $t('supplier.city') }}</Label>
                        <Input id="edit-shop-city" v-model="editForm.city" type="text" required />
                        <p v-if="editForm.errors.city" class="text-sm text-destructive">{{ editForm.errors.city }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-shop-district">{{ $t('supplier.district') }}</Label>
                        <Input id="edit-shop-district" v-model="editForm.district" type="text" required />
                        <p v-if="editForm.errors.district" class="text-sm text-destructive">{{ editForm.errors.district }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="edit-shop-phone">Telephone WhatsApp (optionnel)</Label>
                    <Input id="edit-shop-phone" v-model="editForm.phone" type="text" placeholder="Ex: 6XXXXXXXX" />
                    <p class="text-xs text-muted-foreground">
                        Si ce champ est vide, le systeme utilisera le numero de ton compte utilisateur.
                    </p>
                    <p v-if="editForm.errors.phone" class="text-sm text-destructive">{{ editForm.errors.phone }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="edit-shop-description">{{ $t('common.description') }}</Label>
                    <Textarea id="edit-shop-description" v-model="editForm.description" rows="4" />
                    <p v-if="editForm.errors.description" class="text-sm text-destructive">{{ editForm.errors.description }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="edit-shop-logo">Logo de la boutique (optionnel)</Label>
                    <Input id="edit-shop-logo" type="file" accept="image/*" @change="onEditLogoChange" />
                    <p class="text-xs text-muted-foreground">Format image, max 5MB.</p>
                    <p v-if="editForm.errors.logo" class="text-sm text-destructive">{{ editForm.errors.logo }}</p>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button type="button" variant="outline" @click="editShopDialogOpen = false">
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button type="submit" :disabled="editForm.processing">
                        {{ editForm.processing ? $t('common.saving') : 'Mettre a jour' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

