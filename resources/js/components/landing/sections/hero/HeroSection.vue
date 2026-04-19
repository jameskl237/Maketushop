<script setup>
import HeroContent from '@/components/landing/sections/hero/HeroContent.vue';
import HeroStats from '@/components/landing/sections/hero/HeroStats.vue';
import HeroVisual from '@/components/landing/sections/hero/HeroVisual.vue';
import { router } from '@inertiajs/vue3';

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
    <section id="hero" class="relative overflow-hidden py-20 sm:py-28 lg:py-32">

        <div class="mx-auto max-w-7xl space-y-14 px-5 sm:px-8 lg:px-10">
            <div class="grid items-center gap-14 lg:grid-cols-2">
                <HeroContent @primary="goToProducts" @secondary="goTo('#cta-split')" />
                <HeroVisual />
            </div>
            <HeroStats :stats="stats" />
        </div>
    </section>
</template>
