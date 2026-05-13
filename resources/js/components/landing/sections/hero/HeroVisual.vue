<script setup>
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';

defineProps({
    imageSrc: { type: String, default: '/images/maketa.png' },
    imageAlt: { type: String, default: 'Mascotte MaketuShop' },
});
</script>

<template>
    <ScrollReveal as="div" class="relative mx-auto w-full max-w-lg">
        <!-- Outer ring decoration -->
        <div class="absolute -inset-3 rounded-3xl border border-border/40 opacity-70" />

        <div class="relative overflow-hidden rounded-3xl border border-border/60 bg-card p-6 shadow-[0_4px_24px_-4px_rgba(15,10,30,0.06)]">
            <img
                :src="imageSrc"
                :alt="imageAlt"
                loading="lazy"
                decoding="async"
                class="mx-auto h-[300px] w-full object-contain animate-float sm:h-[400px]"
            />
        </div>
    </ScrollReveal>
</template>
