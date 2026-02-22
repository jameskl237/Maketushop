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
import axios from 'axios';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Boxes, Eye, FolderTree, Package, Plus, Search, Store, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    shop: {
        type: Object,
        required: true,
    },
    products: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const addProductDialogOpen = ref(false);
const deleteProductDialogOpen = ref(false);
const searchQuery = ref('');
const availableCategories = ref([...(props.categories ?? [])]);
const categoriesLoading = ref(false);
const productToDelete = ref(null);

const form = useForm({
    name: '',
    description: '',
    long_description: '',
    price: '',
    promotion_price: '',
    quantity: '',
    origin: 'local',
    in_stock: true,
    category_id: '',
    medias: [],
});

const filteredProducts = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.products;

    return props.products.filter((product) => {
        const values = [
            product.name,
            product.code,
            product.description,
            product.long_description,
            product.price,
            product.promotion_price,
            product.quantity,
            product.origin,
            product.in_stock ? 'en stock' : 'rupture',
            product.category?.name,
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return values.includes(q);
    });
});

const productsCount = computed(() => props.products.length);

const dominantCategory = computed(() => {
    const counts = new Map();
    for (const product of props.products) {
        const categoryName = product.category?.name ?? 'Sans categorie';
        counts.set(categoryName, (counts.get(categoryName) ?? 0) + 1);
    }

    if (counts.size === 0) return 'Aucune';

    let dominant = 'Aucune';
    let max = 0;
    for (const [name, count] of counts.entries()) {
        if (count > max) {
            dominant = name;
            max = count;
        }
    }

    return dominant;
});

const mediaCount = computed(() =>
    props.products.reduce((total, product) => total + (product.medias?.length ?? 0), 0),
);

const onMediaChange = (event) => {
    form.medias = Array.from(event.target.files ?? []);
};

const loadCategories = async () => {
    categoriesLoading.value = true;
    try {
        const { data } = await axios.get(route('backoffice.supplier.categories.index'));
        availableCategories.value = data.categories ?? [];
    } finally {
        categoriesLoading.value = false;
    }
};

const openAddProductModal = async () => {
    addProductDialogOpen.value = true;
    await loadCategories();
};

const submitProduct = () => {
    form.post(route('backoffice.supplier.shops.products.store', { shop: props.shop.id }), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            addProductDialogOpen.value = false;
            form.reset();
        },
    });
};

const goToProductShow = (product) => {
    router.visit(route('backoffice.supplier.shops.products.show', { shop: props.shop.id, product: product.id }));
};

const requestDeleteProduct = (product) => {
    productToDelete.value = product;
    deleteProductDialogOpen.value = true;
};

const confirmDeleteProduct = () => {
    if (!productToDelete.value) return;

    router.delete(route('backoffice.supplier.shops.products.destroy', { shop: props.shop.id, product: productToDelete.value.id }), {
        preserveScroll: true,
        onFinish: () => {
            deleteProductDialogOpen.value = false;
            productToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head :title="`Boutique - ${shop.name}`" />

    <SupplierLayout
        :title="shop.name"
        :subtitle="`${shop.city} - ${shop.district}`"
        active-route="backoffice.supplier.dashboard"
        :can-create-shop="false"
    >
        <template #content>
            <div class="min-w-0 max-w-full space-y-6 overflow-x-hidden">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <Link :href="route('backoffice.supplier.dashboard')" class="text-sm text-muted-foreground hover:text-foreground">
                            ← Retour au dashboard
                        </Link>
                        <div class="flex items-center gap-1.5 rounded-md border border-border px-2 py-1">
                            <span class="max-w-[14rem] truncate text-sm font-medium">{{ shop.name }}</span>
                            <CopyShopLinkButton :shop-id="shop.id" :shop-name="shop.name" />
                        </div>
                    </div>
                    <Button class="w-full gap-2 sm:w-auto" @click="openAddProductModal">
                        <Plus class="h-4 w-4" />
                        Ajouter un produit
                    </Button>
                </div>

                <div class="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Card>
                        <CardContent class="p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Nombre de produits</p>
                                    <p class="mt-1 text-2xl font-semibold">{{ productsCount }}</p>
                                </div>
                                <Package class="h-5 w-5 text-primary" />
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Categorie dominante</p>
                                    <p class="mt-1 text-lg font-semibold">{{ dominantCategory }}</p>
                                </div>
                                <FolderTree class="h-5 w-5 text-primary" />
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Médias uploadés</p>
                                    <p class="mt-1 text-2xl font-semibold">{{ mediaCount }}</p>
                                </div>
                                <Boxes class="h-5 w-5 text-primary" />
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Boutique</p>
                                    <div class="mt-1 flex items-center gap-1.5">
                                        <p class="line-clamp-1 text-lg font-semibold">{{ shop.name }}</p>
                                        <CopyShopLinkButton :shop-id="shop.id" :shop-name="shop.name" />
                                    </div>
                                </div>
                                <Store class="h-5 w-5 text-primary" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card class="min-w-0 max-w-full">
                    <CardHeader>
                        <CardTitle>Produits</CardTitle>
                    </CardHeader>
                    <CardContent class="min-w-0 max-w-full space-y-4 overflow-x-hidden">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                type="text"
                                class="pl-9"
                                placeholder="Rechercher (nom, code, categorie, description, prix...)"
                                aria-label="Recherche produits"
                            />
                        </div>

                        <div v-if="filteredProducts.length === 0" class="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                            Aucun produit trouvé pour cette recherche.
                        </div>

                        <div v-else class="w-full max-w-full overflow-x-auto overscroll-x-contain rounded-lg border border-border">
                            <table class="min-w-[900px] text-sm">
                                <thead class="bg-muted/40">
                                    <tr class="text-left">
                                        <th class="px-4 py-3 font-medium">Code</th>
                                        <th class="px-4 py-3 font-medium">Nom</th>
                                        <th class="px-4 py-3 font-medium">Categorie</th>
                                        <th class="px-4 py-3 font-medium">Prix</th>
                                        <th class="px-4 py-3 font-medium">Stock</th>
                                        <th class="px-4 py-3 font-medium">Quantite</th>
                                        <th class="px-4 py-3 font-medium">Medias</th>
                                        <th class="px-4 py-3 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="product in filteredProducts"
                                        :key="product.id"
                                        class="border-t border-border transition-all duration-300 hover:bg-muted/30"
                                    >
                                        <td class="px-4 py-3 font-medium">{{ product.code }}</td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium">{{ product.name }}</div>
                                            <div class="line-clamp-1 text-xs text-muted-foreground">
                                                {{ product.description || '-' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">{{ product.category?.name || 'Sans categorie' }}</td>
                                        <td class="px-4 py-3">{{ product.price }} FCFA</td>
                                        <td class="px-4 py-3">
                                            <Badge :variant="product.in_stock ? 'secondary' : 'outline'">
                                                {{ product.in_stock ? 'En stock' : 'Rupture' }}
                                            </Badge>
                                        </td>
                                        <td class="px-4 py-3">{{ product.quantity }}</td>
                                        <td class="px-4 py-3">{{ product.medias?.length ?? 0 }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    aria-label="Voir le produit"
                                                    @click="goToProductShow(product)"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    aria-label="Supprimer le produit"
                                                    @click="requestDeleteProduct(product)"
                                                >
                                                    <Trash2 class="h-4 w-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </template>
    </SupplierLayout>

    <Dialog :open="addProductDialogOpen" @update:open="addProductDialogOpen = $event">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>Ajouter un produit</DialogTitle>
                <DialogDescription>
                    Renseigne les informations du produit et ajoute les médias (images / vidéos).
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submitProduct">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="product-name">Nom</Label>
                        <Input id="product-name" v-model="form.name" type="text" required />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="product-category">Categorie</Label>
                        <select
                            id="product-category"
                            v-model="form.category_id"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">Sans categorie</option>
                            <option v-for="category in availableCategories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                        <p v-if="categoriesLoading" class="text-xs text-muted-foreground">Chargement des categories...</p>
                        <p v-else-if="availableCategories.length === 0" class="text-xs text-muted-foreground">
                            Aucune categorie disponible pour le moment.
                        </p>
                        <p v-if="form.errors.category_id" class="text-sm text-destructive">{{ form.errors.category_id }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="product-origin">Origine</Label>
                        <select
                            id="product-origin"
                            v-model="form.origin"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="local">Local</option>
                            <option value="imported">Imported</option>
                        </select>
                        <p v-if="form.errors.origin" class="text-sm text-destructive">{{ form.errors.origin }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="product-price">Prix</Label>
                        <Input id="product-price" v-model="form.price" type="number" min="0" step="0.01" required />
                        <p v-if="form.errors.price" class="text-sm text-destructive">{{ form.errors.price }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="product-promo-price">Prix promo</Label>
                        <Input id="product-promo-price" v-model="form.promotion_price" type="number" min="0" step="0.01" />
                        <p v-if="form.errors.promotion_price" class="text-sm text-destructive">
                            {{ form.errors.promotion_price }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="product-quantity">Quantite</Label>
                        <Input id="product-quantity" v-model="form.quantity" type="number" min="0" step="0.01" required />
                        <p v-if="form.errors.quantity" class="text-sm text-destructive">{{ form.errors.quantity }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="product-stock">Disponibilite</Label>
                        <select
                            id="product-stock"
                            v-model="form.in_stock"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option :value="true">En stock</option>
                            <option :value="false">Rupture</option>
                        </select>
                        <p v-if="form.errors.in_stock" class="text-sm text-destructive">{{ form.errors.in_stock }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="product-description">Description courte</Label>
                    <Input id="product-description" v-model="form.description" type="text" />
                    <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="product-long-description">Description longue</Label>
                    <Textarea id="product-long-description" v-model="form.long_description" rows="4" />
                    <p v-if="form.errors.long_description" class="text-sm text-destructive">
                        {{ form.errors.long_description }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="product-medias">Médias (images/vidéos)</Label>
                    <Input
                        id="product-medias"
                        type="file"
                        multiple
                        accept="image/*,video/*"
                        @change="onMediaChange"
                    />
                    <p class="text-xs text-muted-foreground">
                        Tu peux uploader plusieurs photos et videos (max 10 fichiers).
                    </p>
                    <p v-if="form.errors.medias" class="text-sm text-destructive">{{ form.errors.medias }}</p>
                    <p v-if="form.errors['medias.0']" class="text-sm text-destructive">{{ form.errors['medias.0'] }}</p>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button type="button" variant="outline" @click="addProductDialogOpen = false">Annuler</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creation...' : 'Creer le produit' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog :open="deleteProductDialogOpen" @update:open="deleteProductDialogOpen = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Confirmer la suppression</DialogTitle>
                <DialogDescription>
                    Voulez-vous vraiment supprimer
                    <span class="font-semibold text-foreground">
                        {{ productToDelete?.name ?? 'ce produit' }}
                    </span>
                    ? Cette action est irreversible.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    type="button"
                    variant="outline"
                    @click="deleteProductDialogOpen = false"
                >
                    Annuler
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    @click="confirmDeleteProduct"
                >
                    Supprimer
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

