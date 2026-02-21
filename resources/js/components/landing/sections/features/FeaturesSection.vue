<script setup>
import FeatureGrid from '@/components/landing/sections/features/FeatureGrid.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { useI18n } from 'vue-i18n';

/**
 * @component FeaturesSection
 * @description Section fonctionnalités.
 * @example <FeaturesSection :features="features" />
 */
defineProps({
    features: { type: Array, default: () => [] },
});
const { t } = useI18n();
</script>

<template>
    <section id="features" class="py-16 sm:py-20">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <ScrollReveal>
                <div class="text-center">
                    <h2 class="text-3xl font-bold">{{ t('landing.featuresTitle') }}</h2>
                    <p class="mt-3 text-muted-foreground">{{ t('landing.featuresSubtitle') }}</p>
                </div>
            </ScrollReveal>
            <FeatureGrid :features="features" />
        </div>
    </section>
</template>


