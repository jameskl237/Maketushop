<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    products: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Produits" />

    <SupplierLayout
        title="Produits"
        subtitle="Tous les produits de tes boutiques"
        active-route="backoffice.supplier.products.index"
        :can-create-shop="false"
    >
        <template #content>
            <Card>
                <CardHeader>
                    <CardTitle>Catalogue fournisseur</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="products.length === 0" class="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                        Aucun produit disponible pour le moment.
                    </div>

                    <div v-else class="w-full overflow-x-auto rounded-lg border border-border">
                        <table class="min-w-[850px] text-sm">
                            <thead class="bg-muted/40">
                                <tr class="text-left">
                                    <th class="px-4 py-3 font-medium">Code</th>
                                    <th class="px-4 py-3 font-medium">Nom</th>
                                    <th class="px-4 py-3 font-medium">Boutique</th>
                                    <th class="px-4 py-3 font-medium">Categorie</th>
                                    <th class="px-4 py-3 font-medium">Prix</th>
                                    <th class="px-4 py-3 font-medium">Stock</th>
                                    <th class="px-4 py-3 font-medium">Médias</th>
                                    <th class="px-4 py-3 font-medium">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="product in products"
                                    :key="product.id"
                                    class="border-t border-border"
                                >
                                    <td class="px-4 py-3">{{ product.code }}</td>
                                    <td class="px-4 py-3 font-medium">{{ product.name }}</td>
                                    <td class="px-4 py-3">{{ product.shop?.name || '-' }}</td>
                                    <td class="px-4 py-3">{{ product.category?.name || 'Sans categorie' }}</td>
                                    <td class="px-4 py-3">{{ product.price }} FCFA</td>
                                    <td class="px-4 py-3">
                                        <Badge :variant="product.in_stock ? 'secondary' : 'outline'">
                                            {{ product.in_stock ? 'En stock' : 'Rupture' }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3">{{ product.medias_count ?? 0 }}</td>
                                    <td class="px-4 py-3">
                                        <Link
                                            :href="route('backoffice.supplier.shops.products.show', { shop: product.shop_id, product: product.id })"
                                            class="text-primary hover:underline"
                                        >
                                            Ouvrir
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </template>
    </SupplierLayout>
</template>


