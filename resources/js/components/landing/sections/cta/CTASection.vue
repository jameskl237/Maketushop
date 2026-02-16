<script setup>
import GradientBlob from '@/components/landing/utils/GradientBlob.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';

/**
 * @component CTASection
 * @description CTA final avec fond gradient.
 * @example <CTASection />
 */
</script>

<template>
    <section id="final-cta" class="py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <ScrollReveal>
                <div class="relative overflow-hidden rounded-2xl border border-border bg-gradient-to-r from-primary/15 via-accent/10 to-secondary/15 p-8 sm:p-12">
                    <GradientBlob class="-left-10 -top-8" />
                    <GradientBlob class="-bottom-10 right-0 bg-accent/30" />
                    <div class="relative grid items-center gap-8 lg:grid-cols-[1fr_auto]">
                        <div class="space-y-4">
                            <h2 class="text-3xl font-bold">Prêt à accélérer vos ventes ?</h2>
                            <p class="max-w-2xl text-muted-foreground">
                                Rejoignez une plateforme pensée pour convertir vos visiteurs en clients fidèles.
                            </p>
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <Link :href="route('register')"><Button size="lg">Créer mon compte</Button></Link>
                                <Link :href="route('login')"><Button size="lg" variant="outline">Accéder à mon espace</Button></Link>
                            </div>
                        </div>
                        <img
                            src="/images/Maketu_phone.png"
                            alt="Mascotte de la plateforme"
                            loading="lazy"
                            class="mx-auto h-44 w-auto object-contain"
                        />
                    </div>
                </div>
            </ScrollReveal>
        </div>
    </section>
</template>


