<script setup>
import { Card, CardContent } from '@/components/ui/card';

/**
 * @component FeatureCard
 * @description Carte d'une fonctionnalité.
 * @example <FeatureCard :feature="feature" />
 */
defineProps({
    feature: { type: Object, required: true },
});
</script>

<template>
    <Card class="h-full border-border/70 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
        <CardContent class="space-y-4 p-6">
            <div class="inline-flex rounded-xl bg-primary/10 p-3 text-primary">
                <component :is="feature.icon" class="h-5 w-5" />
            </div>
            <h3 class="text-xl font-semibold">{{ feature.title }}</h3>
            <p class="text-sm text-muted-foreground">{{ feature.description }}</p>
        </CardContent>
    </Card>
</template>


