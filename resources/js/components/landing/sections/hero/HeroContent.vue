<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { useI18n } from 'vue-i18n';

/**
 * @component HeroContent
 * @description Bloc texte principal du hero avec CTA dual.
 * @emits primary
 * @emits secondary
 * @example <HeroContent @primary="..." @secondary="..." />
 */
defineEmits(['primary', 'secondary']);
const { t } = useI18n();
</script>

<template>
    <div class="space-y-6">
        <ScrollReveal>
            <Badge variant="secondary" class="rounded-full px-3 py-1 text-xs">{{ t('landing.heroBadge') }}</Badge>
        </ScrollReveal>

        <ScrollReveal :delay="80">
            <h1 class="text-4xl font-bold tracking-tight text-foreground sm:text-5xl">
                {{ t('landing.heroTitle') }}
            </h1>
        </ScrollReveal>

        <ScrollReveal :delay="160">
            <p class="max-w-2xl text-base text-muted-foreground sm:text-lg">
                {{ t('landing.heroSubtitle') }}
            </p>
        </ScrollReveal>

        <ScrollReveal :delay="220">
            <div class="flex flex-col gap-3 sm:flex-row">
                <Button size="lg" class="h-auto w-full whitespace-normal break-words px-5 py-2 text-center sm:w-auto" @click="$emit('primary')">
                    {{ t('landing.heroBuy') }}
                </Button>
                <Button
                    size="lg"
                    variant="outline"
                    class="h-auto w-full whitespace-normal break-words px-5 py-2 text-center sm:w-auto"
                    @click="$emit('secondary')"
                >
                    {{ t('landing.heroSell') }}
                </Button>
            </div>
        </ScrollReveal>
    </div>
</template>


