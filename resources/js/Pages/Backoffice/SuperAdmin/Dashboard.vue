<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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
        labels: [t('admin.superadmin.dashboard.chartPayoutsLabel'), t('admin.superadmin.dashboard.chartFeesLabel')],
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
    <Head :title="$t('admin.superadmin.dashboard.pageTitle')" />

    <AdminLayout :title="$t('admin.superadmin.role')" :active-route="'backoffice.superadmin.dashboard'">
        <template #content>
            <div class="py-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div class="rounded-lg border border-border bg-card p-3">
                        <div class="text-xs text-muted-foreground">{{ $t('admin.superadmin.dashboard.totalRevenue') }}</div>
                        <div class="text-lg sm:text-2xl font-bold">{{ formatPrice(props.stats.total_revenue) }}</div>
                        <div class="text-xxs text-muted-foreground">{{ $t('admin.superadmin.dashboard.totalRevenueHint') }}</div>
                    </div>
                    <div class="rounded-lg border border-border bg-card p-3">
                        <div class="text-xs text-muted-foreground">{{ $t('admin.superadmin.dashboard.totalPayouts') }}</div>
                        <div class="text-lg sm:text-2xl font-bold">{{ formatPrice(props.stats.total_payouts) }}</div>
                        <div class="text-xxs text-muted-foreground">{{ $t('admin.superadmin.dashboard.totalPayoutsHint') }}</div>
                    </div>
                    <div class="rounded-lg border border-border bg-card p-3">
                        <div class="text-xs text-muted-foreground">{{ $t('admin.superadmin.dashboard.platformFees') }}</div>
                        <div class="text-lg sm:text-2xl font-bold">{{ formatPrice(props.stats.total_platform_fees) }}</div>
                        <div class="text-xxs text-muted-foreground">{{ $t('admin.superadmin.dashboard.platformFeesHint') }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div class="lg:col-span-2 rounded-lg border border-border bg-card p-3">
                        <h3 class="font-semibold mb-2 text-sm">{{ $t('admin.superadmin.dashboard.monthlyRevenueTitle') }}</h3>
                        <canvas ref="lineChartRef" class="w-full h-40 sm:h-56"></canvas>
                    </div>

                    <div class="space-y-3">
                        <div class="rounded-lg border border-border bg-card p-3">
                            <h4 class="font-medium mb-1 text-sm">{{ $t('admin.superadmin.dashboard.payoutVsFees') }}</h4>
                            <canvas ref="donutPayoutRef" class="w-full h-40 sm:h-48"></canvas>
                        </div>

                            <div class="rounded-lg border border-border bg-card p-3">
                                <h4 class="font-medium mb-1 text-sm">{{ $t('admin.superadmin.dashboard.topShopsTitle') }}</h4>
                                <canvas ref="donutTopShopsRef" class="w-full h-40 sm:h-48"></canvas>
                            </div>

                            <div class="rounded-lg border border-border bg-card p-3">
                                <h4 class="font-medium mb-1 text-sm">{{ $t('admin.superadmin.dashboard.topSuppliersTitle') }}</h4>
                                <canvas ref="donutTopSuppliersRef" class="w-full h-40 sm:h-48"></canvas>
                            </div>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-border bg-card p-3 overflow-x-auto">
                    <h3 class="font-semibold mb-2 text-sm">{{ $t('admin.superadmin.dashboard.recentOrdersTitle') }}</h3>
                    <div class="w-full min-w-[640px]">
                        <table class="w-full text-left text-sm">
                            <thead class="text-muted-foreground border-b border-border">
                                <tr>
                                    <th class="p-2">{{ $t('admin.superadmin.dashboard.hash') }}</th>
                                    <th class="p-2">{{ $t('admin.superadmin.dashboard.client') }}</th>
                                    <th class="p-2">{{ $t('admin.superadmin.dashboard.total') }}</th>
                                    <th class="p-2">{{ $t('admin.superadmin.dashboard.status') }}</th>
                                    <th class="p-2">{{ $t('admin.superadmin.dashboard.date') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="order in props.recent_orders" :key="order.id">
                                    <td class="p-2 font-mono text-xs">{{ order.id }}</td>
                                    <td class="p-2">{{ order.user?.name || $t('admin.superadmin.dashboard.client') }}</td>
                                    <td class="p-2 font-bold">{{ formatPrice(order.total_price) }}</td>
                                    <td class="p-2 text-muted-foreground">{{ order.status }}</td>
                                    <td class="p-2 text-muted-foreground">{{ new Date(order.created_at).toLocaleString() }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-border bg-card p-3 overflow-x-auto">
                    <h3 class="font-semibold mb-2 text-sm">{{ $t('admin.superadmin.dashboard.supplierBalancesTitle') }}</h3>
                    <div class="w-full min-w-[640px]">
                        <table class="w-full text-left text-sm">
                            <thead class="text-muted-foreground border-b border-border">
                                <tr>
                                    <th class="p-2">{{ $t('admin.superadmin.dashboard.hash') }}</th>
                                    <th class="p-2">{{ $t('admin.superadmin.dashboard.supplier') }}</th>
                                    <th class="p-2">{{ $t('admin.superadmin.dashboard.sales') }}</th>
                                    <th class="p-2">{{ $t('admin.superadmin.dashboard.payable') }}</th>
                                    <th class="p-2">{{ $t('admin.superadmin.dashboard.withheld') }}</th>
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
            </div>
        </template>
    </AdminLayout>
</template>

<script>
export default {
    methods: {
        formatPrice(value) {
            if (value === null || value === undefined) return this.$t('admin.superadmin.dashboard.zeroAmount');
            return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 }).format(value).replace('XAF', 'FCFA');
        }
    }
}
</script>
