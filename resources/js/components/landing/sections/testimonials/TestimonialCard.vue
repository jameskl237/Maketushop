<script setup>
import { Card, CardContent } from '@/components/ui/card';

/**
 * @component TestimonialCard
 * @description Carte d'avis client.
 * @example <TestimonialCard :testimonial="item" />
 */
defineProps({
    testimonial: { type: Object, required: true },
});
</script>

<template>
    <Card class="border-border/70">
        <CardContent class="space-y-4 p-6">
            <p class="text-sm leading-relaxed text-muted-foreground">"{{ testimonial.quote }}"</p>
            <div class="flex items-center gap-3">
                <img
                    :src="testimonial.avatar"
                    :alt="`Avatar de ${testimonial.name}`"
                    loading="lazy"
                    class="h-10 w-10 rounded-full object-cover"
                />
                <div>
                    <p class="text-sm font-semibold">{{ testimonial.name }}</p>
                    <p class="text-xs text-muted-foreground">{{ testimonial.role }}</p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>


