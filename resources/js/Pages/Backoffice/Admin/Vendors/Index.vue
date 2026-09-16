<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link } from '@inertiajs/vue3';
import { money, date } from '@/lib/orders';

defineProps({ vendors: Object });
</script>

<template>
    <Head title="Vendeurs" />

    <AdminLayout title="Vendeurs">
        <div class="mx-auto max-w-6xl space-y-5 p-4 sm:p-6">
            <h1 class="text-xl font-semibold">Vendeurs</h1>

            <div class="overflow-x-auto rounded-lg border border-border bg-card">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Vendeur</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Boutiques</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Produits</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">Disponible</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">En attente</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">Inscrit le</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="v in vendors.data" :key="v.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3">
                                <span class="font-medium">{{ v.name }}</span>
                                <span class="block text-xs text-muted-foreground">{{ v.email }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">{{ v.shops_count }}</td>
                            <td class="px-4 py-3 text-right">{{ v.products_count }}</td>
                            <td class="px-4 py-3 text-right font-medium text-primary">{{ money(v.balance?.available_balance) }}</td>
                            <td class="px-4 py-3 text-right">{{ money(v.balance?.pending_balance) }}</td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">{{ date(v.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('backoffice.admin.vendors.show', v.id)" class="text-primary hover:underline">Détail</Link>
                            </td>
                        </tr>
                        <tr v-if="!vendors.data.length">
                            <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">Aucun vendeur.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="vendors.links" :links="vendors.links" />
        </div>
    </AdminLayout>
</template>
