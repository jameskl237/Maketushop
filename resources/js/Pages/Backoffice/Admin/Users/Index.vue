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
                <div class="overflow-hidden border border-border bg-card shadow sm:rounded-lg">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted/40">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Rôle</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="user in users" :key="user.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">{{ user.id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">{{ user.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">{{ user.username }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ user.email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ user.google_id ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ user.role }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ user.phone ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ user.address ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ user.email_verified_at ? new Date(user.email_verified_at).toLocaleString() : '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('backoffice.admin.users.show', { user: user.id })" class="text-primary hover:text-primary/80">Voir</Link>
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
