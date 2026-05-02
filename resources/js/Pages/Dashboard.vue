<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ShoppingBag, Calendar, CheckCircle } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XAF',
    }).format(price);
};

const statusLabel = (status) => {
    const labels = {
        pending: 'dashboard.client.pending',
        delivered: 'dashboard.client.delivered',
    };

    return labels[status] || 'dashboard.client.pending';
};

const statusBadgeClass = (status) => {
    return status === 'delivered'
        ? 'bg-green-500/10 text-green-600 border-green-500/20'
        : 'bg-amber-500/10 text-amber-600 border-amber-500/20';
};

const markAsDelivered = (order) => {
    router.patch(route('orders.delivered', { order: order.id }), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="$t('dashboard.client.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-foreground">
                        {{ $t('dashboard.client.title') }}
                    </h2>
                    <p class="text-muted-foreground">
                        {{ $t('dashboard.client.subtitle') }}
                    </p>
                </div>
                <Link :href="route('products.index')">
                    <Button>
                        <ShoppingBag class="mr-2 h-4 w-4" />
                        {{ $t('public.products') }}
                    </Button>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                <!-- Stats Overview (Optional) -->
                <div class="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                {{ $t('dashboard.client.orders') }}
                            </CardTitle>
                            <ShoppingBag class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ orders.length }}</div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Recent Orders -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ $t('dashboard.client.orders') }}</CardTitle>
                        <CardDescription>
                            {{ orders.length > 0 ? $t('dashboard.client.orders') : $t('dashboard.client.noOrders') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="orders.length > 0" class="space-y-4">
                            <div v-for="order in orders" :key="order.id" class="flex flex-col md:flex-row md:items-center justify-between p-4 border border-border rounded-lg hover:bg-accent/50 transition-colors gap-4">
                                <div class="flex items-start gap-4">
                                    <div class="p-2 bg-primary/10 rounded-full text-primary">
                                        <ShoppingBag class="h-6 w-6" />
                                    </div>
                                    <div class="space-y-1">
                                        <p class="font-bold text-foreground">
                                            {{ order.order_number }}
                                        </p>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                            <span class="flex items-center gap-1">
                                                <Calendar class="h-3 w-3" />
                                                {{ formatDate(order.created_at) }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <ShoppingBag class="h-3 w-3" />
                                                {{ $t('dashboard.client.items', { count: order.total_products }) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col items-stretch gap-3 border-t pt-4 md:w-auto md:flex-row md:items-center md:justify-end md:border-t-0 md:pt-0">
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-foreground">
                                            {{ formatPrice(order.total_price) }}
                                        </p>
                                        <Badge variant="outline" :class="statusBadgeClass(order.status)">
                                            {{ $t(statusLabel(order.status)) }}
                                        </Badge>
                                    </div>
                                    <Button
                                        v-if="order.status === 'pending'"
                                        type="button"
                                        variant="outline"
                                        class="w-full md:w-auto"
                                        @click="markAsDelivered(order)"
                                    >
                                        <CheckCircle class="mr-2 h-4 w-4" />
                                        {{ $t('dashboard.client.markDelivered') }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 border-2 border-dashed border-border rounded-lg">
                            <ShoppingBag class="mx-auto h-12 w-12 text-muted-foreground mb-4 opacity-20" />
                            <p class="text-muted-foreground">{{ $t('dashboard.client.noOrders') }}</p>
                            <Link :href="route('products.index')" class="mt-4 inline-block">
                                <Button variant="outline">{{ $t('landing.heroBuy') }}</Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
