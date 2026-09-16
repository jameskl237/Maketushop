<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { datetime } from '@/lib/orders';

defineProps({ logs: Object });

const expanded = ref(null);

const variant = (status) =>
    status === 'processed' ? 'default' : status === 'error' ? 'destructive' : 'secondary';
</script>

<template>
    <Head title="Journal des webhooks" />

    <AdminLayout title="Journal des webhooks">
        <div class="mx-auto max-w-6xl space-y-5 p-4 sm:p-6">
            <h1 class="text-xl font-semibold">Journal des webhooks</h1>

            <div class="space-y-2">
                <div v-for="log in logs.data" :key="log.id" class="rounded-lg border border-border bg-card">
                    <button type="button" class="flex w-full flex-wrap items-center justify-between gap-3 p-4 text-left hover:bg-muted/30"
                            @click="expanded = expanded === log.id ? null : log.id">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge :variant="variant(log.status)">{{ log.status }}</Badge>
                                <span class="font-mono text-xs">{{ log.transaction_id ?? '—' }}</span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ log.provider }} / {{ log.event }} — {{ datetime(log.created_at) }} — {{ log.ip_address }}
                            </p>
                            <p v-if="log.error" class="mt-1 text-xs text-destructive">{{ log.error?.message }}</p>
                        </div>
                        <span class="text-xs text-muted-foreground">{{ expanded === log.id ? 'Masquer' : 'Voir la charge utile' }}</span>
                    </button>

                    <pre v-if="expanded === log.id"
                         class="max-h-80 overflow-auto border-t border-border bg-muted/30 p-4 text-xs">{{ JSON.stringify(log.payload, null, 2) }}</pre>
                </div>

                <div v-if="!logs.data.length" class="rounded-lg border border-border bg-card p-10 text-center text-sm text-muted-foreground">
                    Aucune notification reçue.
                </div>
            </div>

            <Pagination v-if="logs.links" :links="logs.links" />
        </div>
    </AdminLayout>
</template>
