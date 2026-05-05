<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { 
    TrendingUp, 
    ShoppingBag, 
    CreditCard, 
    Store,
    PieChart,
    BarChart3,
    Activity
} from 'lucide-vue-next';

const props = defineProps({ 
    stats: Object, 
    revenue_by_category: Array, 
    revenue_by_payment_method: Array, 
    orders_by_status: Array, 
    revenue_over_time: Object,
    top_shops: Array
});

// Refs for Chart.js instances
const revenueTimeChartRef = ref(null);
const categoryChartRef = ref(null);
const paymentMethodChartRef = ref(null);
const orderStatusChartRef = ref(null);
const topShopsChartRef = ref(null);

const formatPrice = (value) => {
    if (value === null || value === undefined) return '0 FCFA';
    return new Intl.NumberFormat(undefined, { 
        style: 'currency', 
        currency: 'XAF', 
        maximumFractionDigits: 0 
    }).format(value).replace('XAF', 'FCFA');
};

onMounted(async () => {
    try {
        const { Chart, registerables } = await import('chart.js');
        Chart.register(...registerables);

        // 1. Revenue Over Time (Bar/Line Chart)
        if (revenueTimeChartRef.value) {
            new Chart(revenueTimeChartRef.value.getContext('2d'), {
                type: 'line',
                data: props.revenue_over_time,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => `Revenu: ${formatPrice(context.raw)}`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => formatPrice(value)
                            }
                        }
                    }
                },
            });
        }

        // 2. Revenue by Category (Doughnut Chart)
        if (categoryChartRef.value) {
            new Chart(categoryChartRef.value.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: props.revenue_by_category.map(c => c.name),
                    datasets: [{
                        data: props.revenue_by_category.map(c => c.total),
                        backgroundColor: [
                            '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#6366f1', 
                            '#8b5cf6', '#ec4899', '#14b8a6', '#f43f5e', '#06b6d4'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.label}: ${formatPrice(context.raw)}`
                            }
                        }
                    }
                }
            });
        }

        // 3. Revenue by Payment Method (Pie Chart)
        if (paymentMethodChartRef.value) {
            new Chart(paymentMethodChartRef.value.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: props.revenue_by_payment_method.map(p => p.method),
                    datasets: [{
                        data: props.revenue_by_payment_method.map(p => p.total),
                        backgroundColor: ['#f97316', '#06b6d4', '#8b5cf6', '#64748b']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.label}: ${formatPrice(context.raw)}`
                            }
                        }
                    }
                }
            });
        }

        // 4. Orders by Status (Bar Chart)
        if (orderStatusChartRef.value) {
            new Chart(orderStatusChartRef.value.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: props.orders_by_status.map(s => s.status),
                    datasets: [{
                        label: 'Nombre de commandes',
                        data: props.orders_by_status.map(s => s.count),
                        backgroundColor: '#6366f1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        // 5. Top Shops by Revenue (Horizontal Bar Chart)
        if (topShopsChartRef.value) {
            new Chart(topShopsChartRef.value.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: props.top_shops.map(s => s.name),
                    datasets: [{
                        label: 'Revenu Total',
                        data: props.top_shops.map(s => s.total),
                        backgroundColor: '#10b981'
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => `Revenu: ${formatPrice(context.raw)}`
                            }
                        }
                    }
                }
            });
        }

    } catch (e) {
        console.error('Erreur lors du chargement de Chart.js', e);
    }
});
</script>

<template>
    <Head title="Comptabilité — SuperAdmin" />

    <AdminLayout title="Comptabilité SuperAdmin" active-route="backoffice.superadmin.accounting">
        <template #content>
            <div class="p-6 space-y-8">
                <!-- Header Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-card border border-border rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-muted-foreground">Revenu Total</span>
                            <TrendingUp class="w-4 h-4 text-primary" />
                        </div>
                        <div class="text-2xl font-bold">{{ formatPrice(props.stats.total_revenue) }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Commandes livrées uniquement</p>
                    </div>

                    <div class="bg-card border border-border rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-muted-foreground">Commandes</span>
                            <ShoppingBag class="w-4 h-4 text-indigo-500" />
                        </div>
                        <div class="text-2xl font-bold">{{ props.stats.total_orders }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Toutes les commandes confondues</p>
                    </div>

                    <div class="bg-card border border-border rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-muted-foreground">Frais Plateforme</span>
                            <CreditCard class="w-4 h-4 text-emerald-500" />
                        </div>
                        <div class="text-2xl font-bold">{{ formatPrice(props.stats.total_platform_fees) }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Estimation (10% de commission)</p>
                    </div>

                    <div class="bg-card border border-border rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-muted-foreground">Boutiques Actives</span>
                            <Store class="w-4 h-4 text-orange-500" />
                        </div>
                        <div class="text-2xl font-bold">{{ props.stats.active_shops }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Boutiques enregistrées sur la plateforme</p>
                    </div>
                </div>

                <!-- Main Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Revenue Over Time -->
                    <div class="lg:col-span-2 bg-card border border-border rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-2 mb-6">
                            <Activity class="w-5 h-5 text-primary" />
                            <h3 class="font-semibold text-lg">Évolution du Revenu (30 derniers jours)</h3>
                        </div>
                        <div class="h-80 w-full">
                            <canvas ref="revenueTimeChartRef"></canvas>
                        </div>
                    </div>

                    <!-- Revenue by Payment Method -->
                    <div class="bg-card border border-border rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-2 mb-6">
                            <PieChart class="w-5 h-5 text-primary" />
                            <h3 class="font-semibold text-lg">Méthodes de Paiement</h3>
                        </div>
                        <div class="h-80 w-full">
                            <canvas ref="paymentMethodChartRef"></canvas>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Revenue by Category -->
                    <div class="bg-card border border-border rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-2 mb-6">
                            <PieChart class="w-5 h-5 text-primary" />
                            <h3 class="font-semibold text-lg">Revenu par Catégorie</h3>
                        </div>
                        <div class="h-80 w-full">
                            <canvas ref="categoryChartRef"></canvas>
                        </div>
                    </div>

                    <!-- Top Shops -->
                    <div class="bg-card border border-border rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-2 mb-6">
                            <BarChart3 class="w-5 h-5 text-primary" />
                            <h3 class="font-semibold text-lg">Top 5 Boutiques (Revenu)</h3>
                        </div>
                        <div class="h-80 w-full">
                            <canvas ref="topShopsChartRef"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Order Status -->
                <div class="bg-card border border-border rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-2 mb-6">
                        <BarChart3 class="w-5 h-5 text-primary" />
                        <h3 class="font-semibold text-lg">Répartition des Commandes par Statut</h3>
                    </div>
                    <div class="h-64 w-full">
                        <canvas ref="orderStatusChartRef"></canvas>
                    </div>
                </div>
            </div>
        </template>
    </AdminLayout>
</template>

<style scoped>
canvas {
    width: 100% !important;
}
</style>
