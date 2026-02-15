<script setup>
import StatCard from '@/components/supplier/StatCard.vue';

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
</script>

<template>
    <!-- Exemple: <StatsOverview :stats="stats" :loading="false" /> -->
    <section aria-label="Résumé des statistiques">
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

