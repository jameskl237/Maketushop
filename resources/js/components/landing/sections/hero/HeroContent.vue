<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { useI18n } from 'vue-i18n';
import { ArrowRight } from 'lucide-vue-next';

defineEmits(['primary', 'secondary']);
const { t } = useI18n();
</script>

<template>
    <div class="space-y-7">
        <ScrollReveal>
            <div class="section-label">
                <span>{{ t('landing.heroBadge') }}</span>
            </div>
        </ScrollReveal>

        <ScrollReveal :delay="80">
            <h1 class="font-display text-5xl font-semibold leading-[1.12] tracking-tight text-foreground sm:text-6xl lg:text-[4.25rem]">
                {{ t('landing.heroTitle') }}
            </h1>
        </ScrollReveal>

        <ScrollReveal :delay="160">
            <p class="max-w-xl text-[15px] leading-relaxed text-muted-foreground sm:text-base">
                {{ t('landing.heroSubtitle') }}
            </p>
        </ScrollReveal>

        <ScrollReveal :delay="240">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <Button
                    size="lg"
                    class="group h-12 rounded-full px-8 text-[13px] font-semibold tracking-wide shadow-sm transition-all duration-300 hover:shadow-md hover:-translate-y-0.5"
                    @click="$emit('primary')"
                >
                    {{ t('landing.heroBuy') }}
                    <ArrowRight class="ml-2 h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" />
                </Button>
                <Button
                    size="lg"
                    variant="outline"
                    class="h-12 rounded-full px-8 text-[13px] font-semibold tracking-wide transition-all duration-300 hover:-translate-y-0.5"
                    @click="$emit('secondary')"
                >
                    {{ t('landing.heroSell') }}
                </Button>
            </div>
        </ScrollReveal>
    </div>
</template>
