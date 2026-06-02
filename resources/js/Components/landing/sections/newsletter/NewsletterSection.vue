<script setup>
import NewsletterForm from '@/components/landing/sections/newsletter/NewsletterForm.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <section id="newsletter" class="py-20 sm:py-28">
        <div class="mx-auto max-w-4xl px-5 sm:px-8 lg:px-10">
            <ScrollReveal>
                <div class="overflow-hidden rounded-3xl border border-border/60 bg-card px-8 py-12 sm:px-12 shadow-sm">
                    <div class="mx-auto max-w-xl text-center">
                        <div class="section-label mx-auto mb-3 justify-center">
                            <span>Newsletter</span>
                        </div>
                        <h2 class="font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                            {{ t('landing.newsletterTitle') }}
                        </h2>
                        <p class="mt-3 text-[13px] leading-relaxed text-muted-foreground">
                            {{ t('landing.newsletterText') }}
                        </p>
                        <div class="mt-7">
                            <NewsletterForm />
                        </div>
                    </div>
                </div>
            </ScrollReveal>
        </div>
    </section>
</template>
