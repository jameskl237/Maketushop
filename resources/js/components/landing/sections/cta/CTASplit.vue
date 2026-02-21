<script setup>
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

/**
 * @component CTASplit
 * @description Double CTA acheteur/vendeur.
 * @example <CTASplit />
 */
const { t } = useI18n();
</script>

<template>
    <section id="cta-split" class="py-16 sm:py-20">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
            <ScrollReveal>
                <Card class="h-full border-border/70">
                    <CardContent class="space-y-4 p-8">
                        <h3 class="text-2xl font-bold">{{ t('landing.splitCustomerTitle') }}</h3>
                        <p class="text-muted-foreground">{{ t('landing.splitCustomerText') }}</p>
                        <Link :href="route('products.index')">
                            <Button size="lg" class="h-auto whitespace-normal break-words px-5 py-2 text-center">{{ t('landing.splitCustomerButton') }}</Button>
                        </Link>
                    </CardContent>
                </Card>
            </ScrollReveal>
            <ScrollReveal :delay="120">
                <Card class="h-full border-primary/40 bg-primary/5">
                    <CardContent class="space-y-4 p-8">
                        <h3 class="text-2xl font-bold">{{ t('landing.splitSellerTitle') }}</h3>
                        <p class="text-muted-foreground">{{ t('landing.splitSellerText') }}</p>
                        <Link :href="route('register')">
                            <Button size="lg" variant="outline" class="h-auto whitespace-normal break-words px-5 py-2 text-center">
                                {{ t('landing.splitSellerButton') }}
                            </Button>
                        </Link>
                    </CardContent>
                </Card>
            </ScrollReveal>
        </div>
    </section>
</template>


