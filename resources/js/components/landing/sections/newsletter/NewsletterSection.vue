<script setup>
import NewsletterForm from '@/components/landing/sections/newsletter/NewsletterForm.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';

/**
 * @component NewsletterSection
 * @description Section newsletter de conversion.
 * @example <NewsletterSection />
 */
</script>

<template>
    <section id="newsletter" class="py-16 sm:py-20">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <ScrollReveal>
                <div class="rounded-2xl border border-border bg-card p-8 sm:p-10">
                    <h2 class="text-3xl font-bold">Restez informé des nouveautés</h2>
                    <p class="mt-3 text-muted-foreground">
                        Recevez nos meilleures offres, conseils vendeurs et tendances du marché.
                    </p>
                    <div class="mt-6">
                        <NewsletterForm />
                    </div>
                </div>
            </ScrollReveal>
        </div>
    </section>
</template>


