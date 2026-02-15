<script setup>
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import StepCard from '@/components/landing/sections/how-it-works/StepCard.vue';

/**
 * @component ProcessTimeline
 * @description Timeline simple en 3 étapes.
 * @example <ProcessTimeline :steps="steps" />
 */
defineProps({
    steps: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="grid gap-6 md:grid-cols-3">
        <ScrollReveal
            v-for="(step, idx) in steps"
            :key="step.title"
            :delay="idx * 80"
        >
            <StepCard :step="step" :index="idx + 1" />
        </ScrollReveal>
    </div>
</template>


