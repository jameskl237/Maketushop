<script setup>
import HeroContent from '@/components/landing/sections/hero/HeroContent.vue';
import HeroStats from '@/components/landing/sections/hero/HeroStats.vue';
import HeroVisual from '@/components/landing/sections/hero/HeroVisual.vue';
import { router } from '@inertiajs/vue3';

/**
 * @component HeroSection
 * @description Section Hero complète (contenu, visuel, stats).
 * @example <HeroSection :stats="heroStats" />
 */
defineProps({
    stats: { type: Array, default: () => [] },
});

const goTo = (selector) => {
    document.querySelector(selector)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const goToProducts = () => {
    router.visit(route('products.index'));
};
</script>

<template>
    <section id="hero" class="relative overflow-hidden py-16 sm:py-20">
        <div class="mx-auto max-w-7xl space-y-10 px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-10 lg:grid-cols-2">
                <HeroContent @primary="goToProducts" @secondary="goTo('#cta-split')" />
                <HeroVisual />
            </div>
            <HeroStats :stats="stats" />
        </div>
    </section>
</template>


