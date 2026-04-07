<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps({
    stats: Object,
});
</script>

<template>
    <Head title="Admin — Dashboard" />

    <AdminLayout :title="$t('admin.dashboardTitle')" :active-route="'backoffice.admin.dashboard'">
        <template #content>
            <div class="py-8">
                <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:px-8 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Utilisateurs</CardTitle>
                            <CardDescription>Total utilisateurs</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-semibold">{{ props.stats.users_total }}</div>
                            <div class="text-sm text-muted-foreground">Admins: {{ props.stats.users_admin }} — Fournisseurs: {{ props.stats.users_supplier }}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Boutiques</CardTitle>
                            <CardDescription>Total boutiques</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-semibold">{{ props.stats.shops }}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Produits</CardTitle>
                            <CardDescription>Total produits</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-semibold">{{ props.stats.products }}</div>
                            <div class="text-sm text-muted-foreground">Catégories: {{ props.stats.categories }} — Commandes: {{ props.stats.orders }}</div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </template>
    </AdminLayout>
</template>

