<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    shops: Object,
});
</script>

<template>
    <Head :title="$t('admin.shops.pageTitle')" />

    <AdminLayout :title="$t('admin.shops.title')" :active-route="'backoffice.admin.shops.index'">
        <template #content>
            <div class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h3 class="text-lg font-medium">{{ $t('admin.shops.listTitle') }}</h3>
                    <div class="mt-4 border border-border bg-card shadow sm:rounded-lg">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted/40">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ $t('admin.shops.id') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ $t('admin.shops.name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ $t('admin.shops.city') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="shop in props.shops.data" :key="shop.id">
                                    <td class="px-6 py-4 text-sm text-foreground">{{ shop.id }}</td>
                                    <td class="px-6 py-4 text-sm text-foreground">{{ shop.name }}</td>
                                    <td class="px-6 py-4 text-sm text-muted-foreground">{{ shop.city }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </AdminLayout>
</template>
