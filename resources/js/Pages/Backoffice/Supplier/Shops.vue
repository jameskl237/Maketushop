<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import CopyShopLinkButton from '@/components/supplier/CopyShopLinkButton.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogScrollContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    shops: {
        type: Array,
        default: () => [],
    },
});

const showCreateDialog = ref(false);

const form = useForm({
    name: '',
    description: '',
    city: '',
    district: '',
    phone: '',
});

const submit = () => {
    form.post(route('backoffice.supplier.shops.store'), {
        onSuccess: () => {
            showCreateDialog.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <Head :title="$t('supplier.shops')" />

    <SupplierLayout
        :title="$t('supplier.shops')"
        :subtitle="$t('supplier.shopsSubtitle')"
        active-route="backoffice.supplier.shops.index"
        :can-create-shop="true"
        @create-shop="showCreateDialog = true"
    >
        <template #content>
            <Card>
                <CardHeader>
                    <CardTitle>{{ $t('supplier.shopsList') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="shops.length === 0" class="rounded-lg border border-dashed border-border p-8 text-center">
                        <p class="text-sm text-muted-foreground">{{ $t('supplier.noShops') }}</p>
                        <Button class="mt-4" @click="showCreateDialog = true">
                            {{ $t('supplier.createShop') }}
                        </Button>
                    </div>

                    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div
                            v-for="shop in shops"
                            :key="shop.id"
                            class="rounded-lg border border-border p-4"
                        >
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-1.5">
                                    <h3 class="line-clamp-1 text-base font-semibold">{{ shop.name }}</h3>
                                    <CopyShopLinkButton :shop-id="shop.id" :shop-name="shop.name" />
                                </div>
                                <Badge variant="secondary">{{ shop.products_count }} {{ $t('supplier.products') }}</Badge>
                            </div>
                            <p class="line-clamp-2 text-sm text-muted-foreground">
                                {{ shop.description || $t('supplier.noDescription') }}
                            </p>
                            <p class="mt-2 text-xs text-muted-foreground">{{ shop.city }} - {{ shop.district }}</p>

                            <Link
                                :href="route('backoffice.supplier.shops.show', { shop: shop.id })"
                                class="mt-4 inline-block text-sm font-medium text-primary hover:underline"
                            >
                                {{ $t('supplier.viewProducts') }}
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </template>
    </SupplierLayout>

    <!-- Dialog création de boutique -->
    <Dialog :open="showCreateDialog" @update:open="showCreateDialog = $event">
        <DialogScrollContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ $t('supplier.createShop') }}</DialogTitle>
                    <DialogDescription>{{ $t('supplier.createShopDescription') }}</DialogDescription>
                </DialogHeader>

                <!-- Scrollable area for the form fields so the footer stays visible on small screens -->
                <div class="max-h-[60vh] overflow-y-auto pr-2">
                    <form id="create-shop-form" @submit.prevent="submit" class="space-y-4">
                        <div>
                            <Label for="shop-name">{{ $t('supplier.shopName') }} *</Label>
                            <Input
                                id="shop-name"
                                v-model="form.name"
                                type="text"
                                class="mt-1"
                                required
                                :disabled="form.processing"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <Label for="shop-description">{{ $t('common.description') }}</Label>
                            <Textarea
                                id="shop-description"
                                v-model="form.description"
                                class="mt-1"
                                rows="3"
                                :disabled="form.processing"
                            />
                            <p v-if="form.errors.description" class="mt-1 text-xs text-destructive">{{ form.errors.description }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <Label for="shop-city">{{ $t('supplier.city') }} *</Label>
                                <Input
                                    id="shop-city"
                                    v-model="form.city"
                                    type="text"
                                    class="mt-1"
                                    required
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.city" class="mt-1 text-xs text-destructive">{{ form.errors.city }}</p>
                            </div>
                            <div>
                                <Label for="shop-district">{{ $t('supplier.district') }} *</Label>
                                <Input
                                    id="shop-district"
                                    v-model="form.district"
                                    type="text"
                                    class="mt-1"
                                    required
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.district" class="mt-1 text-xs text-destructive">{{ form.errors.district }}</p>
                            </div>
                        </div>

                        <div>
                            <Label for="shop-phone">{{ $t('common.phone') }} *</Label>
                            <Input
                                id="shop-phone"
                                v-model="form.phone"
                                type="text"
                                class="mt-1"
                                placeholder="Ex: 237600000000"
                                required
                                :disabled="form.processing"
                            />
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-destructive">{{ form.errors.phone }}</p>
                        </div>
                    </form>
                </div>

                <!-- Footer left outside the scrollable area so actions remain visible on small screens -->
                <DialogFooter>
                    <Button type="button" variant="outline" :disabled="form.processing" @click="showCreateDialog = false">
                        {{ $t('common.cancel') }}
                    </Button>
                    <!-- Use the `form` attribute to target the moved form so this button still submits -->
                    <Button type="submit" :disabled="form.processing" form="create-shop-form">
                        {{ form.processing ? $t('common.saving') : $t('supplier.createShop') }}
                    </Button>
                </DialogFooter>
        </DialogScrollContent>
    </Dialog>
</template>
