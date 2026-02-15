<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';

/**
 * @component HeroContent
 * @description Bloc texte principal du hero avec CTA dual.
 * @emits primary
 * @emits secondary
 * @example <HeroContent @primary="..." @secondary="..." />
 */
defineEmits(['primary', 'secondary']);
</script>

<template>
    <div class="space-y-6">
        <ScrollReveal>
            <Badge variant="secondary" class="rounded-full px-3 py-1 text-xs">Marketplace e-commerce locale</Badge>
        </ScrollReveal>

        <ScrollReveal :delay="80">
            <h1 class="text-4xl font-bold tracking-tight text-foreground sm:text-5xl">
                Vendez et achetez localement avec une expérience premium.
            </h1>
        </ScrollReveal>

        <ScrollReveal :delay="160">
            <p class="max-w-2xl text-base text-muted-foreground sm:text-lg">
                MaketuShop aide les vendeurs à lancer leur boutique en ligne et les clients à découvrir des produits de qualité, rapidement.
            </p>
        </ScrollReveal>

        <ScrollReveal :delay="220">
            <div class="flex flex-col gap-3 sm:flex-row">
                <Button size="lg" class="w-full sm:w-auto" @click="$emit('primary')">Je veux acheter</Button>
                <Button size="lg" variant="outline" class="w-full sm:w-auto" @click="$emit('secondary')">Je veux vendre</Button>
            </div>
        </ScrollReveal>
    </div>
</template>


