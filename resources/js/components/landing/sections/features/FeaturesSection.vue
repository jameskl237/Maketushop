<script setup>
import FeatureGrid from '@/components/landing/sections/features/FeatureGrid.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { useI18n } from 'vue-i18n';

defineProps({
    features: { type: Array, default: () => [] },
});
const { t } = useI18n();
</script>

<template>
    <section id="features" class="relative py-24 sm:py-32">
        <div class="mx-auto max-w-7xl space-y-16 px-6 sm:px-8 lg:px-10">
            <ScrollReveal>
                <div class="flex flex-col items-center text-center">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-primary/10 bg-primary/5 px-4 py-1.5 backdrop-blur-sm">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-primary"></span>
                        </span>
                        <span class="text-[11px] font-black uppercase tracking-[0.2em] text-primary">{{ t('landing.featuresTitle') }}</span>
                    </div>
                    <h2 class="max-w-2xl font-display text-[36px] font-black leading-[1.1] tracking-tighter text-foreground sm:text-6xl">
                        Tout ce dont vous avez besoin pour <span class="bg-gradient-to-r from-primary via-pink to-primary bg-[length:200%_auto] bg-clip-text text-transparent animate-gradient">réussir</span>
                    </h2>
                    <p class="mx-auto mt-6 max-w-xl text-[16px] leading-relaxed text-muted-foreground/80">
                        {{ t('landing.featuresSubtitle') }}
                    </p>
                </div>
            </ScrollReveal>
            <FeatureGrid :features="features" />
        </div>
    </section>
</template>

<style scoped>
@keyframes gradient {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
.animate-gradient {
  animation: gradient 3s ease infinite;
}
</style>
