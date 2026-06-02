<script setup>
import TestimonialCarousel from '@/components/landing/sections/testimonials/TestimonialCarousel.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { Card, CardContent } from '@/components/ui/card';

/**
 * @component TestimonialsSection
 * @description Section preuves sociales + carousel.
 * @example <TestimonialsSection :testimonials="items" />
 */
defineProps({
    testimonials: { type: Array, default: () => [] },
});
</script>

<template>
    <section id="testimonials" class="py-16 sm:py-20">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <ScrollReveal>
                <div class="text-center">
                    <h2 class="text-3xl font-bold">Ce que disent nos utilisateurs</h2>
                    <p class="mt-3 text-muted-foreground">Des retours concrets qui renforcent la confiance.</p>
                </div>
            </ScrollReveal>

            <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
                <ScrollReveal>
                    <TestimonialCarousel :items="testimonials" />
                </ScrollReveal>
                <ScrollReveal :delay="100">
                    <Card class="border-border/70">
                        <CardContent class="space-y-5 p-6">
                            <div>
                                <p class="text-3xl font-bold">4.9/5</p>
                                <p class="text-sm text-muted-foreground">Note moyenne clients</p>
                            </div>
                            <div>
                                <p class="text-3xl font-bold">98%</p>
                                <p class="text-sm text-muted-foreground">Satisfaction livraison</p>
                            </div>
                            <div>
                                <p class="text-3xl font-bold">24h</p>
                                <p class="text-sm text-muted-foreground">Temps de réponse moyen</p>
                            </div>
                        </CardContent>
                    </Card>
                </ScrollReveal>
            </div>
        </div>
    </section>
</template>


