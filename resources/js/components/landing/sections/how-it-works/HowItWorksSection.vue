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
    <section id="how-it-works" class="relative py-24 sm:py-32">
        <!-- Floating shapes background for this section -->
        <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 -right-20 w-80 h-80 bg-pink/5 rounded-full blur-[100px]"></div>
        </div>

        <div class="relative z-10 mx-auto max-w-7xl space-y-20 px-6 sm:px-8 lg:px-10">
            <ScrollReveal>
                <div class="flex flex-col items-center text-center">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-pink/10 bg-pink/5 px-4 py-1.5 backdrop-blur-sm">
                        <span class="text-[11px] font-black uppercase tracking-[0.2em] text-pink-600">{{ t('landing.howTitle') }}</span>
                    </div>
                    <h2 class="max-w-2xl font-display text-[36px] font-black leading-[1.1] tracking-tighter text-foreground sm:text-6xl">
                        Un processus <span class="text-primary italic">simple</span> pour des résultats <span class="bg-gradient-to-r from-pink to-primary bg-clip-text text-transparent">exceptionnels</span>
                    </h2>
                    <p class="mx-auto mt-6 max-w-xl text-[16px] leading-relaxed text-muted-foreground/80">
                        {{ t('landing.howSubtitle') }}
                    </p>
                </div>
            </ScrollReveal>

            <div class="relative">
                <!-- Premium Timeline Background -->
                <div class="absolute left-1/2 top-0 h-full w-px -translate-x-1/2 bg-gradient-to-b from-primary/30 via-pink/30 to-transparent hidden md:block"></div>
                <ProcessTimeline :steps="steps" />
            </div>
        </div>
    </section>
</template>
