<script setup>
import PartnerLogos from '@/components/landing/sections/partners/PartnerLogos.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';

/**
 * @component PartnersSection
 * @description Bloc preuve sociale "trusted by".
 * @example <PartnersSection :logos="partnerLogos" />
 */
defineProps({
    logos: { type: Array, default: () => [] },
});
</script>

<template>
    <section id="partners" class="py-14 sm:py-16">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <ScrollReveal>
                <p class="text-center text-sm font-medium uppercase tracking-wide text-muted-foreground">
                    Ils nous font confiance
                </p>
            </ScrollReveal>
            <ScrollReveal :delay="80">
                <PartnerLogos :logos="logos" />
            </ScrollReveal>
        </div>
    </section>
</template>


