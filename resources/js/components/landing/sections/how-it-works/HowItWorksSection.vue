<script setup>
import ProcessTimeline from '@/components/landing/sections/how-it-works/ProcessTimeline.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { useI18n } from 'vue-i18n';

defineProps({
    steps: { type: Array, default: () => [] },
});
const { t } = useI18n();
</script>

<template>
    <section id="how-it-works" class="py-20 sm:py-28 bg-muted/40">
        <div class="mx-auto max-w-7xl space-y-12 px-5 sm:px-8 lg:px-10">
            <ScrollReveal>
                <div class="text-center">
                    <div class="section-label mx-auto mb-3 justify-center">
                        <span>{{ t('landing.howTitle') }}</span>
                    </div>
                    <h2 class="font-display text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">
                        {{ t('landing.howTitle') }}
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-[14px] leading-relaxed text-muted-foreground">
                        {{ t('landing.howSubtitle') }}
                    </p>
                </div>
            </ScrollReveal>
            <ProcessTimeline :steps="steps" />
        </div>
    </section>
</template>
