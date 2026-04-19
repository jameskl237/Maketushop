<script setup>
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ArrowRight } from 'lucide-vue-next';

const { t } = useI18n();
</script>

<template>
    <section id="final-cta" class="py-20 sm:py-28 bg-muted/40">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <ScrollReveal>
                <div class="relative overflow-hidden rounded-3xl border border-border/60 bg-card px-8 py-14 sm:px-14 shadow-sm">
                    <div class="relative grid items-center gap-10 lg:grid-cols-[1fr_auto]">
                        <div class="space-y-6">
                            <div class="section-label">
                                <span>{{ t('landing.ctaTitle') }}</span>
                            </div>
                            <h2 class="font-display text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">
                                {{ t('landing.ctaTitle') }}
                            </h2>
                            <p class="max-w-xl text-[14px] leading-relaxed text-muted-foreground">
                                {{ t('landing.ctaSubtitle') }}
                            </p>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <Link :href="route('register')">
                                    <Button size="lg" class="group h-12 rounded-full px-8 text-[13px] font-semibold tracking-wide shadow-sm hover:-translate-y-0.5 hover:shadow-md">
                                        {{ t('landing.ctaCreateAccount') }}
                                        <ArrowRight class="ml-2 h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" />
                                    </Button>
                                </Link>
                                <Link :href="route('login')">
                                    <Button size="lg" variant="outline" class="h-12 rounded-full px-8 text-[13px] font-semibold tracking-wide hover:-translate-y-0.5">
                                        {{ t('landing.ctaAccessSpace') }}
                                    </Button>
                                </Link>
                            </div>
                        </div>
                        <img
                            src="/images/Maketu_phone.png"
                            alt="Application MaketuShop"
                            loading="lazy"
                            class="mx-auto h-52 w-auto object-contain drop-shadow-xl"
                        />
                    </div>
                </div>
            </ScrollReveal>
        </div>
    </section>
</template>
