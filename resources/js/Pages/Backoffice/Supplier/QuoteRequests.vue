<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';

defineProps({
    quoteRequests: { type: Array, default: () => [] },
});

const markHandled = (quote) => {
    router.patch(route('backoffice.supplier.quote-requests.handled', { quoteRequest: quote.id }), {}, {
        preserveScroll: true,
    });
};

const formatDate = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head :title="$t('supplier.quoteRequests')" />

    <SupplierLayout
        :title="$t('supplier.quoteRequests')"
        :subtitle="$t('supplier.quoteRequestsSubtitle')"
        active-route="backoffice.supplier.quote-requests.index"
        :can-create-shop="false"
    >
        <template #content>
            <Card>
                <CardHeader>
                    <CardTitle>{{ $t('supplier.quoteRequests') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="quoteRequests.length === 0" class="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                        {{ $t('supplier.noQuoteRequests') }}
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="quote in quoteRequests"
                            :key="quote.id"
                            class="rounded-xl border border-border p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-foreground">{{ quote.service?.title || 'Service supprimé' }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ quote.customer_name || quote.user?.name || 'Client' }}
                                        <span v-if="quote.customer_phone"> · {{ quote.customer_phone }}</span>
                                    </p>
                                </div>
                                <Badge :variant="quote.status === 'traite' ? 'secondary' : 'outline'">
                                    {{ quote.status === 'traite' ? 'Traité' : 'En attente' }}
                                </Badge>
                            </div>

                            <p v-if="quote.budget" class="mt-2 text-sm">
                                <span class="text-muted-foreground">Budget estimé :</span> {{ quote.budget }}
                            </p>
                            <p v-if="quote.message" class="mt-1 text-sm text-muted-foreground">{{ quote.message }}</p>

                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-[11px] text-muted-foreground">{{ formatDate(quote.created_at) }}</span>
                                <Button
                                    v-if="quote.status !== 'traite'"
                                    size="sm"
                                    variant="outline"
                                    @click="markHandled(quote)"
                                >
                                    <Check class="h-4 w-4" />
                                    Marquer traité
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </template>
    </SupplierLayout>
</template>
