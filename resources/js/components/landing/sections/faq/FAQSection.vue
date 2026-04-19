<script setup>
import FAQItem from '@/components/landing/sections/faq/FAQItem.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { useI18n } from 'vue-i18n';

defineProps({
    faqs: { type: Array, default: () => [] },
});
const { t } = useI18n();
</script>

<template>
    <section id="faq" class="py-20 sm:py-28">
        <div class="mx-auto max-w-3xl space-y-10 px-5 sm:px-8 lg:px-10">
            <ScrollReveal>
                <div class="text-center">
                    <div class="section-label mx-auto mb-3 justify-center">
                        <span>FAQ</span>
                    </div>
                    <h2 class="font-display text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">
                        {{ t('landing.faqTitle') }}
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-[14px] leading-relaxed text-muted-foreground">
                        {{ t('landing.faqSubtitle') }}
                    </p>
                </div>
            </ScrollReveal>

            <div class="space-y-2.5">
                <ScrollReveal
                    v-for="(faq, idx) in faqs"
                    :key="faq.question"
                    :delay="idx * 60"
                >
                    <FAQItem :item="faq" />
                </ScrollReveal>
            </div>
        </div>
    </section>
</template>
