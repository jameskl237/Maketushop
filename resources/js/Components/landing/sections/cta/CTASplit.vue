<script setup>
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ShoppingBag, Store } from 'lucide-vue-next';

const { t } = useI18n();
</script>

<template>
    <section id="cta-split" class="py-20 sm:py-28">
        <div class="mx-auto grid max-w-7xl gap-5 px-5 sm:px-8 lg:grid-cols-2 lg:px-10">
            <!-- Acheteur -->
            <ScrollReveal>
                <div class="group relative overflow-hidden rounded-3xl border border-border/60 bg-card p-10 shadow-sm transition-all duration-300 hover:shadow-md">
                    <div class="relative space-y-5">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-muted text-foreground ring-1 ring-border">
                            <ShoppingBag class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="font-display text-2xl font-semibold text-foreground">{{ t('landing.splitCustomerTitle') }}</h3>
                            <p class="mt-2 text-[13px] leading-relaxed text-muted-foreground">{{ t('landing.splitCustomerText') }}</p>
                        </div>
                        <Link :href="route('products.index')">
                            <Button size="lg" class="h-11 rounded-full px-7 text-[13px] font-semibold tracking-wide shadow-sm">
                                {{ t('landing.splitCustomerButton') }}
                            </Button>
                        </Link>
                    </div>
                </div>
            </ScrollReveal>

            <!-- Vendeur -->
            <ScrollReveal :delay="120">
                <div class="group relative overflow-hidden rounded-3xl border border-primary/20 bg-card p-10 shadow-sm transition-all duration-300 hover:shadow-md">
                    <div class="relative space-y-5">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/8 text-primary ring-1 ring-primary/20">
                            <Store class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="font-display text-2xl font-semibold text-foreground">{{ t('landing.splitSellerTitle') }}</h3>
                            <p class="mt-2 text-[13px] leading-relaxed text-muted-foreground">{{ t('landing.splitSellerText') }}</p>
                        </div>
                        <Link :href="route('register')">
                            <Button size="lg" variant="outline" class="h-11 rounded-full border-primary/30 px-7 text-[13px] font-semibold tracking-wide hover:bg-primary/6">
                                {{ t('landing.splitSellerButton') }}
                            </Button>
                        </Link>
                    </div>
                </div>
            </ScrollReveal>
        </div>
    </section>
</template>
