<script setup>
import ProcessTimeline from '@/components/landing/sections/how-it-works/ProcessTimeline.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { useI18n } from 'vue-i18n';

/**
 * @component HowItWorksSection
 * @description Section "comment ça marche".
 * @example <HowItWorksSection :steps="steps" />
 */
defineProps({
    steps: { type: Array, default: () => [] },
});
const { t } = useI18n();
</script>

<template>
    <section id="how-it-works" class="py-16 sm:py-20">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <ScrollReveal>
                <div class="text-center">
                    <h2 class="text-3xl font-bold">{{ t('landing.howTitle') }}</h2>
                    <p class="mt-3 text-muted-foreground">{{ t('landing.howSubtitle') }}</p>
                </div>
            </ScrollReveal>
            <ProcessTimeline :steps="steps" />
        </div>
    </section>
</template>


