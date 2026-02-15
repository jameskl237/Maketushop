<script setup>
import GradientBlob from '@/components/landing/utils/GradientBlob.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';

/**
 * @component HeroVisual
 * @description Visuel mascotte avec décor gradient.
 * @example <HeroVisual image-src="/images/mascot/hero.webp" />
 */
defineProps({
    imageSrc: { type: String, default: '/images/maketa.png' },
    imageAlt: { type: String, default: 'Mascotte MaketuShop' },
});
</script>

<template>
    <ScrollReveal as="div" class="relative mx-auto w-full max-w-xl">
        <GradientBlob class="-left-6 top-8" />
        <GradientBlob class="-right-6 bottom-2 bg-accent/30" />
        <div class="relative overflow-hidden rounded-2xl border border-border bg-card/70 p-4 shadow-xl backdrop-blur">
            <img
                :src="imageSrc"
                :alt="imageAlt"
                loading="lazy"
                decoding="async"
                class="mx-auto h-[320px] w-full object-contain animate-float sm:h-[420px]"
            />
        </div>
    </ScrollReveal>
</template>


