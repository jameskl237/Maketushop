<script setup>
import FeatureCard from '@/components/landing/sections/features/FeatureCard.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';

/**
 * @component FeatureGrid
 * @description Grille responsive des fonctionnalités.
 * @example <FeatureGrid :features="features" />
 */
defineProps({
    features: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <ScrollReveal
            v-for="(feature, idx) in features"
            :key="feature.title"
            :delay="idx * 80"
        >
            <FeatureCard :feature="feature" />
        </ScrollReveal>
    </div>
</template>


