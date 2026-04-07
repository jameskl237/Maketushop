<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    shops: Object,
});
</script>

<template>
    <Head title="Admin — Boutiques" />

    <AdminLayout :title="'Gestion — Boutiques'" :active-route="'backoffice.admin.shops.index'">
        <template #content>
            <div class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h3 class="text-lg font-medium">Liste des boutiques</h3>
                    <div class="mt-4 bg-white shadow sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ville</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="shop in props.shops.data" :key="shop.id">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ shop.id }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ shop.name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ shop.city }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </AdminLayout>
</template>
