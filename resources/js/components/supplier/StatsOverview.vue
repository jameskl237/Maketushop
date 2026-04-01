<script setup>
import StatCard from '@/components/supplier/StatCard.vue';
import { useI18n } from 'vue-i18n';

defineProps({
    stats: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const { t } = useI18n();
</script>

<template>
    <!-- Exemple: <StatsOverview :stats="stats" :loading="false" /> -->
    <section :aria-label="t('supplier.statsOverviewAria')">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                v-for="(item, index) in stats"
                :key="`stat-${index}`"
                :title="item.title"
                :value="item.value"
                :hint="item.hint"
                :icon="item.icon"
                :trend-type="item.trendType"
                :loading="loading"
            />
        </div>
    </section>
</template>
