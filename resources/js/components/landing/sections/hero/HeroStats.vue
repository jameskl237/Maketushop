<script setup>
import AnimatedCounter from '@/components/landing/utils/AnimatedCounter.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { Card, CardContent } from '@/components/ui/card';

/**
 * @component HeroStats
 * @description Cartes de stats avec compteurs animés.
 * @example <HeroStats :stats="heroStats" />
 */
defineProps({
    stats: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-3">
        <ScrollReveal
            v-for="(stat, idx) in stats"
            :key="stat.label"
            :delay="idx * 90"
        >
            <Card class="border-border/70 bg-card/60">
                <CardContent class="p-4">
                    <p class="text-2xl font-bold text-foreground">
                        <AnimatedCounter :value="stat.value" :suffix="stat.suffix" />
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">{{ stat.label }}</p>
                </CardContent>
            </Card>
        </ScrollReveal>
    </div>
</template>


