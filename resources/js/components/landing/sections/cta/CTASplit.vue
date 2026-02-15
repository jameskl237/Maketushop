<script setup>
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';

/**
 * @component CTASplit
 * @description Double CTA acheteur/vendeur.
 * @example <CTASplit />
 */
</script>

<template>
    <section id="cta-split" class="py-16 sm:py-20">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
            <ScrollReveal>
                <Card class="h-full border-border/70">
                    <CardContent class="space-y-4 p-8">
                        <h3 class="text-2xl font-bold">Vous cherchez des produits fiables ?</h3>
                        <p class="text-muted-foreground">Découvrez des vendeurs vérifiés et commandez en toute confiance.</p>
                        <Link :href="route('register')"><Button size="lg">Je suis client</Button></Link>
                    </CardContent>
                </Card>
            </ScrollReveal>
            <ScrollReveal :delay="120">
                <Card class="h-full border-primary/40 bg-primary/5">
                    <CardContent class="space-y-4 p-8">
                        <h3 class="text-2xl font-bold">Vous voulez vendre en ligne ?</h3>
                        <p class="text-muted-foreground">Créez votre boutique, publiez vos produits et développez votre activité.</p>
                        <Link :href="route('register')"><Button size="lg" variant="outline">Je suis vendeur</Button></Link>
                    </CardContent>
                </Card>
            </ScrollReveal>
        </div>
    </section>
</template>


