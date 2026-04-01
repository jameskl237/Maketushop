<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import CopyShopLinkButton from '@/components/supplier/CopyShopLinkButton.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ImageIcon, Plus, Save, Trash2, Video } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    shop: { type: Object, required: true },
    product: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
});

const productForm = useForm({
    name: props.product.name ?? '',
    description: props.product.description ?? '',
    long_description: props.product.long_description ?? '',
    price: props.product.price ?? '',
    promotion_price: props.product.promotion_price ?? '',
    quantity: props.product.quantity ?? '',
    origin: props.product.origin ?? 'local',
    in_stock: Boolean(props.product.in_stock),
    category_id: props.product.category_id ?? '',
});

const mediasForm = useForm({
    medias: [],
});
const deleteMediaDialogOpen = ref(false);
const mediaToDelete = ref(null);
const { t } = useI18n();

const onMediasChange = (event) => {
    mediasForm.medias = Array.from(event.target.files ?? []);
};

const submitProductUpdate = () => {
    productForm.put(route('backoffice.supplier.shops.products.update', {
        shop: props.shop.id,
        product: props.product.id,
    }), {
        preserveScroll: true,
    });
};

const submitMediaUpload = () => {
    mediasForm.post(route('backoffice.supplier.shops.products.medias.store', {
        shop: props.shop.id,
        product: props.product.id,
    }), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => mediasForm.reset(),
    });
};

const requestDeleteMedia = (media) => {
    mediaToDelete.value = media;
    deleteMediaDialogOpen.value = true;
};

const confirmDeleteMedia = () => {
    if (!mediaToDelete.value) return;

    router.delete(route('backoffice.supplier.shops.products.medias.destroy', {
        shop: props.shop.id,
        product: props.product.id,
        media: mediaToDelete.value.id,
    }), {
        preserveScroll: true,
        onFinish: () => {
            deleteMediaDialogOpen.value = false;
            mediaToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head :title="`${t('supplier.product')} - ${product.name}`" />

    <SupplierLayout
        :title="product.name"
        :subtitle="t('supplier.productManagementSubtitle', { shop: shop.name })"
        active-route="backoffice.supplier.dashboard"
        :can-create-shop="false"
    >
        <template #content>
            <div class="space-y-6">
                <Link
                    :href="route('backoffice.supplier.shops.show', { shop: shop.id })"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    {{ t('supplier.backToShopProducts') }}
                </Link>
                <div class="flex items-center gap-1.5 rounded-md border border-border px-2 py-1 w-fit">
                    <span class="text-sm font-medium">{{ shop.name }}</span>
                    <CopyShopLinkButton :shop-id="shop.id" :shop-name="shop.name" />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('supplier.productInformation') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form class="space-y-4" @submit.prevent="submitProductUpdate">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-2 sm:col-span-2">
                                    <Label for="name">{{ t('supplier.name') }}</Label>
                                    <Input id="name" v-model="productForm.name" type="text" required />
                                    <p v-if="productForm.errors.name" class="text-sm text-destructive">{{ productForm.errors.name }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="category_id">{{ t('supplier.category') }}</Label>
                                    <select
                                        id="category_id"
                                        v-model="productForm.category_id"
                                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="">{{ t('supplier.noCategory') }}</option>
                                        <option v-for="category in categories" :key="category.id" :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <Label for="origin">{{ t('supplier.origin') }}</Label>
                                    <select
                                        id="origin"
                                        v-model="productForm.origin"
                                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="local">{{ t('supplier.local') }}</option>
                                        <option value="imported">{{ t('supplier.imported') }}</option>
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <Label for="price">{{ t('supplier.price') }}</Label>
                                    <Input id="price" v-model="productForm.price" type="number" min="0" step="0.01" required />
                                </div>

                                <div class="space-y-2">
                                    <Label for="promotion_price">{{ t('supplier.promoPrice') }}</Label>
                                    <Input id="promotion_price" v-model="productForm.promotion_price" type="number" min="0" step="0.01" />
                                </div>

                                <div class="space-y-2">
                                    <Label for="quantity">{{ t('supplier.quantityLabel') }}</Label>
                                    <Input id="quantity" v-model="productForm.quantity" type="number" min="0" step="0.01" required />
                                </div>

                                <div class="space-y-2">
                                    <Label for="in_stock">{{ t('supplier.availability') }}</Label>
                                    <select
                                        id="in_stock"
                                        v-model="productForm.in_stock"
                                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option :value="true">{{ t('supplier.inStock') }}</option>
                                        <option :value="false">{{ t('supplier.outOfStock') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="description">{{ t('supplier.shortDescription') }}</Label>
                                <Input id="description" v-model="productForm.description" type="text" />
                            </div>

                            <div class="space-y-2">
                                <Label for="long_description">{{ t('supplier.longDescription') }}</Label>
                                <Textarea id="long_description" v-model="productForm.long_description" rows="4" />
                            </div>

                            <Button type="submit" class="gap-2" :disabled="productForm.processing">
                                <Save class="h-4 w-4" />
                                {{ productForm.processing ? t('supplier.updating') : t('supplier.updateProduct') }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('supplier.productMedia') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <form class="space-y-3" @submit.prevent="submitMediaUpload">
                            <Label for="medias">{{ t('supplier.addMediaLabel') }}</Label>
                            <Input
                                id="medias"
                                type="file"
                                multiple
                                accept="image/*,video/*"
                                @change="onMediasChange"
                            />
                            <Button type="submit" class="gap-2" :disabled="mediasForm.processing">
                                <Plus class="h-4 w-4" />
                                {{ mediasForm.processing ? t('supplier.uploading') : t('supplier.addMedia') }}
                            </Button>
                        </form>

                        <div v-if="!product.medias || product.medias.length === 0" class="rounded-lg border border-dashed border-border p-6 text-sm text-muted-foreground">
                            {{ t('supplier.noMedia') }}
                        </div>

                        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="media in product.medias"
                                :key="media.id"
                                class="rounded-lg border border-border p-3"
                            >
                                <div class="mb-2 flex items-center justify-between">
                                    <Badge variant="secondary">
                                        <ImageIcon v-if="media.type === 'image'" class="mr-1 h-3 w-3" />
                                        <Video v-else class="mr-1 h-3 w-3" />
                                        {{ media.type }}
                                    </Badge>
                                    <Button
                                        size="icon"
                                        variant="outline"
                                        :aria-label="t('supplier.deleteMediaAria')"
                                        @click="requestDeleteMedia(media)"
                                    >
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>

                                <img
                                    v-if="media.type === 'image'"
                                    :src="media.full_url"
                                    :alt="t('supplier.productMediaAlt')"
                                    class="h-40 w-full rounded object-cover"
                                />
                                <video
                                    v-else
                                    :src="media.full_url"
                                    controls
                                    class="h-40 w-full rounded bg-black object-cover"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </template>
    </SupplierLayout>

    <Dialog :open="deleteMediaDialogOpen" @update:open="deleteMediaDialogOpen = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('supplier.deleteConfirmTitle') }}</DialogTitle>
                <DialogDescription>
                    {{ t('supplier.deleteMediaConfirmDescription') }}
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    type="button"
                    variant="outline"
                    @click="deleteMediaDialogOpen = false"
                >
                    {{ t('common.cancel') }}
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    @click="confirmDeleteMedia"
                >
                    {{ t('common.delete') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
