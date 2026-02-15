<script setup>
import FAQItem from '@/components/landing/sections/faq/FAQItem.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';

/**
 * @component FAQSection
 * @description Section FAQ.
 * @example <FAQSection :faqs="faqs" />
 */
defineProps({
    faqs: { type: Array, default: () => [] },
});
</script>

<template>
    <section id="faq" class="py-16 sm:py-20">
        <div class="mx-auto max-w-4xl space-y-8 px-4 sm:px-6 lg:px-8">
            <ScrollReveal>
                <div class="text-center">
                    <h2 class="text-3xl font-bold">Questions fréquentes</h2>
                    <p class="mt-3 text-muted-foreground">Tout ce qu’il faut savoir pour démarrer rapidement.</p>
                </div>
            </ScrollReveal>

            <div class="space-y-3">
                <ScrollReveal
                    v-for="(faq, idx) in faqs"
                    :key="faq.question"
                    :delay="idx * 70"
                >
                    <FAQItem :item="faq" />
                </ScrollReveal>
            </div>
        </div>
    </section>
</template>


