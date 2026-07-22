<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    categories: Object,
});
</script>

<template>
    <Head :title="$t('admin.categories.pageTitle')" />

    <AdminLayout :title="$t('admin.categories.title')" :active-route="'backoffice.admin.categories.index'">
        <template #content>
            <div class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h3 class="text-lg font-medium">{{ $t('admin.categories.listTitle') }}</h3>
                    <div class="mt-4 border border-border bg-card shadow sm:rounded-lg">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted/40">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ $t('admin.categories.id') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ $t('admin.categories.name') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="category in props.categories.data" :key="category.id">
                                    <td class="px-6 py-4 text-sm text-foreground">{{ category.id }}</td>
                                    <td class="px-6 py-4 text-sm text-foreground">{{ category.name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </AdminLayout>
</template>
