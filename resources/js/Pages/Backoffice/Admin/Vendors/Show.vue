<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { money, datetime } from '@/lib/orders';

defineProps({
    vendor: Object,
    stats: Object,
    transactions: Array,
});
</script>

<template>
    <Head :title="vendor.name" />

    <AdminLayout :title="vendor.name">
        <div class="mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
            <div>
                <h1 class="text-xl font-semibold">{{ vendor.name }}</h1>
                <p class="text-sm text-muted-foreground">{{ vendor.email }} — {{ vendor.phone ?? 'téléphone non renseigné' }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Disponible</p>
                    <p class="mt-1 text-2xl font-semibold text-primary">{{ money(stats.available_balance) }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">En attente</p>
                    <p class="mt-1 text-2xl font-semibold">{{ money(stats.pending_balance) }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Total gagné</p>
                    <p class="mt-1 text-2xl font-semibold">{{ money(stats.total_earned) }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Total retiré</p>
                    <p class="mt-1 text-2xl font-semibold">{{ money(stats.total_withdrawn) }}</p>
                </div>
            </div>

            <div class="rounded-lg border border-border bg-card p-5">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Boutiques</h2>
                <ul v-if="vendor.shops?.length" class="space-y-1 text-sm">
                    <li v-for="shop in vendor.shops" :key="shop.id" class="flex justify-between gap-3">
                        <span>{{ shop.name }}</span>
                        <span class="text-muted-foreground">{{ shop.phone ?? '—' }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">Aucune boutique.</p>
            </div>

            <div class="rounded-lg border border-border bg-card">
                <h2 class="border-b border-border px-5 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Mouvements du portefeuille
                </h2>
                <div v-if="!transactions?.length" class="p-5 text-sm text-muted-foreground">Aucun mouvement.</div>
                <table v-else class="min-w-full divide-y divide-border text-sm">
                    <tbody class="divide-y divide-border">
                        <tr v-for="t in transactions" :key="t.id">
                            <td class="px-5 py-2.5">
                                <p>{{ t.description }}</p>
                                <p class="text-xs text-muted-foreground">{{ t.type }} — {{ datetime(t.created_at) }}</p>
                            </td>
                            <td class="px-5 py-2.5 text-right font-medium"
                                :class="t.direction === 'debit' ? 'text-destructive' : 'text-primary'">
                                {{ t.direction === 'debit' ? '−' : '+' }}{{ money(t.amount) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Link :href="route('backoffice.admin.vendors.index')" class="inline-block text-sm text-primary hover:underline">
                ← Tous les vendeurs
            </Link>
        </div>
    </AdminLayout>
</template>
