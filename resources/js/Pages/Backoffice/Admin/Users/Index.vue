<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Object,
});

const users = ref(props.users.data || []);
</script>

<template>
    <Head title="Admin — Users" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold leading-tight">Gestion des utilisateurs</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="user in users" :key="user.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ user.id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ user.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.role }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('profile.edit')" class="text-indigo-600 hover:text-indigo-900">Edit</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <!-- Pagination links are provided by the server as `links` — In a complete implementation you would render them here -->
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
