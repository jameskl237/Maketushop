<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';

const props = defineProps({ stats: Object, recent_orders: Array, balances: Array, chart: Object });

const lineChartRef = ref(null);
const donutPayoutRef = ref(null);
const donutTopShopsRef = ref(null);
const donutTopSuppliersRef = ref(null);

const donutData = computed(() => {
    // Payout vs Fees
    const payouts = props.stats.total_payouts || 0;
    const fees = props.stats.total_platform_fees || 0;
    return {
        labels: ['Payouts', 'Platform Fees'],
        datasets: [{ data: [payouts, fees], backgroundColor: ['#06b6d4', '#ef4444'] }],
    };
});

const topShopsData = computed(() => {
    const shops = (props.balances || []).slice(0, 5);
    return {
        labels: shops.map(s => s.name),
        datasets: [{ data: shops.map(s => s.payable || 0), backgroundColor: ['#f97316', '#60a5fa', '#34d399', '#f43f5e', '#a78bfa'] }],
    };
});

const topSuppliersData = computed(() => {
    const suppliers = (props.supplier_balances || []).slice(0, 6);
    return {
        labels: suppliers.map(s => s.name),
        datasets: [{ data: suppliers.map(s => s.payable || 0), backgroundColor: ['#60a5fa', '#34d399', '#f97316', '#a78bfa', '#f43f5e', '#06b6d4'] }],
    };
});

onMounted(async () => {
    try {
        const { Chart, registerables } = await import('chart.js');
        Chart.register(...registerables);

        // Line chart
        if (lineChartRef.value) {
            new Chart(lineChartRef.value.getContext('2d'), {
                type: 'line',
                data: props.chart,
                options: { responsive: true, plugins: { legend: { display: false } } },
            });
        }

        // Donut: Payout vs Fees
        if (donutPayoutRef.value) {
            new Chart(donutPayoutRef.value.getContext('2d'), {
                type: 'doughnut',
                data: donutData.value,
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
            });
        }

        // Donut: Top shops
        if (donutTopShopsRef.value) {
            new Chart(donutTopShopsRef.value.getContext('2d'), {
                type: 'doughnut',
                data: topShopsData.value,
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
            });
        }

        // Donut: Top suppliers
        if (donutTopSuppliersRef.value) {
            new Chart(donutTopSuppliersRef.value.getContext('2d'), {
                type: 'doughnut',
                data: topSuppliersData.value,
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
            });
        }
    } catch (e) {
        // Chart.js not installed — charts will not render
        // console.warn('Chart.js not available', e);
    }
});
</script>

<template>
    <Head title="SuperAdmin — Dashboard" />

    <AdminLayout :title="'SuperAdmin'" :active-route="'backoffice.superadmin.dashboard'">
        <template #content>
            <div class="py-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="rounded-lg border border-border bg-card p-4">
                        <div class="text-sm text-muted-foreground">Revenu total</div>
                        <div class="text-2xl font-bold">{{ formatPrice(props.stats.total_revenue) }}</div>
                        <div class="text-xs text-muted-foreground">Total des commandes livrées</div>
                    </div>
                    <div class="rounded-lg border border-border bg-card p-4">
                        <div class="text-sm text-muted-foreground">Payouts totaux</div>
                        <div class="text-2xl font-bold">{{ formatPrice(props.stats.total_payouts) }}</div>
                        <div class="text-xs text-muted-foreground">Montants à verser aux fournisseurs</div>
                    </div>
                    <div class="rounded-lg border border-border bg-card p-4">
                        <div class="text-sm text-muted-foreground">Frais plateforme</div>
                        <div class="text-2xl font-bold">{{ formatPrice(props.stats.total_platform_fees) }}</div>
                        <div class="text-xs text-muted-foreground">Rétention plateforme</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 rounded-lg border border-border bg-card p-4">
                        <h3 class="font-semibold mb-3">Revenu mensuel (6 derniers mois)</h3>
                        <canvas ref="lineChartRef" class="w-full h-64"></canvas>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-lg border border-border bg-card p-4">
                            <h4 class="font-medium mb-2">Payout vs Fees</h4>
                            <canvas ref="donutPayoutRef" class="w-full h-48"></canvas>
                        </div>

                            <div class="rounded-lg border border-border bg-card p-4">
                                <h4 class="font-medium mb-2">Top boutiques (payable)</h4>
                                <canvas ref="donutTopShopsRef" class="w-full h-48"></canvas>
                            </div>

                            <div class="rounded-lg border border-border bg-card p-4">
                                <h4 class="font-medium mb-2">Top fournisseurs (payable)</h4>
                                <canvas ref="donutTopSuppliersRef" class="w-full h-48"></canvas>
                            </div>
                    </div>
                </div>

                <div class="mt-6 rounded-lg border border-border bg-card p-4">
                    <h3 class="font-semibold mb-3">Commandes récentes</h3>
                    <table class="w-full text-left text-sm">
                        <thead class="text-muted-foreground border-b border-border">
                            <tr>
                                <th class="p-2">#</th>
                                <th class="p-2">Client</th>
                                <th class="p-2">Total</th>
                                <th class="p-2">Statut</th>
                                <th class="p-2">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="order in props.recent_orders" :key="order.id">
                                <td class="p-2 font-mono text-xs">{{ order.id }}</td>
                                <td class="p-2">{{ order.user?.name || 'Client' }}</td>
                                <td class="p-2 font-bold">{{ formatPrice(order.total_price) }}</td>
                                <td class="p-2 text-muted-foreground">{{ order.status }}</td>
                                <td class="p-2 text-muted-foreground">{{ new Date(order.created_at).toLocaleString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 rounded-lg border border-border bg-card p-4">
                    <h3 class="font-semibold mb-3">Soldes par fournisseur</h3>
                    <table class="w-full text-left text-sm">
                        <thead class="text-muted-foreground border-b border-border">
                            <tr>
                                <th class="p-2">#</th>
                                <th class="p-2">Fournisseur</th>
                                <th class="p-2">Ventes</th>
                                <th class="p-2">Payable</th>
                                <th class="p-2">Retenu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="s in props.supplier_balances" :key="s.id">
                                <td class="p-2 font-mono text-xs">{{ s.id }}</td>
                                <td class="p-2">{{ s.name }}</td>
                                <td class="p-2">{{ formatPrice(s.total_sales) }}</td>
                                <td class="p-2">{{ formatPrice(s.payable) }}</td>
                                <td class="p-2">{{ formatPrice(s.withheld) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </AdminLayout>
</template>

<script>
export default {
    methods: {
        formatPrice(value) {
            if (value === null || value === undefined) return '0 FCFA';
            return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 }).format(value).replace('XAF', 'FCFA');
        }
    }
}
</script>
