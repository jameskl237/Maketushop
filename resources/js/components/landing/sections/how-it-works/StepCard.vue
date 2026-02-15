<script setup>
import { Card, CardContent } from '@/components/ui/card';

/**
 * @component StepCard
 * @description Carte d'étape process.
 * @example <StepCard :step="step" :index="1" />
 */
defineProps({
    step: { type: Object, required: true },
    index: { type: Number, required: true },
});
</script>

<template>
    <Card class="border-border/70">
        <CardContent class="space-y-3 p-6">
            <div class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">
                {{ index }}
            </div>
            <h3 class="text-xl font-semibold">{{ step.title }}</h3>
            <p class="text-sm text-muted-foreground">{{ step.description }}</p>
        </CardContent>
    </Card>
</template>


