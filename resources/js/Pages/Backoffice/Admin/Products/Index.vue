<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    products: Object,
});
</script>

<template>
    <Head title="Admin — Produits" />

    <AdminLayout :title="'Gestion — Produits'" :active-route="'backoffice.admin.products.index'">
        <template #content>
            <div class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h3 class="text-lg font-medium">Liste des produits</h3>
                    <div class="mt-4 bg-white shadow sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="product in props.products.data" :key="product.id">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ product.id }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ product.name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ product.price }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </AdminLayout>
</template>
