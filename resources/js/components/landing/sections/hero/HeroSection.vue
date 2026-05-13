<script setup>
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { router } from '@inertiajs/vue3';
import { ArrowRight, Store } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

defineProps({
    stats: { type: Array, default: () => [] },
});

const { t } = useI18n();

const goTo = (selector) => {
    document.querySelector(selector)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const goToProducts = () => {
    router.visit(route('products.index'));
};
</script>

<template>
    <section id="hero" class="relative overflow-hidden bg-gradient-to-b from-[#0b1020] via-[#0f0529] to-[#0b1020] px-4 pb-16 pt-12 text-white">
        <!-- Layered Gradient Shapes -->
        <div class="absolute -left-40 -top-24 h-96 w-96 rounded-full blur-3xl bg-gradient-to-tr from-[#7c3aed]/40 to-[#06b6d4]/30 animate-blob-slow"></div>
        <div class="absolute right-[-10%] top-12 h-72 w-72 rounded-full blur-2xl bg-gradient-to-bl from-[#fb7185]/30 to-[#f97316]/20 animate-blob-slow [animation-delay:2s]"></div>
        <div class="absolute inset-x-0 bottom-0 h-64 pointer-events-none bg-gradient-to-t from-black/50 to-transparent"></div>

        <div class="relative mx-auto max-w-7xl">
            <div class="flex flex-col items-center text-center">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/3 px-3 py-1 backdrop-blur-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-rose-300">{{ t('landing.heroBadge') }}</p>
                </div>

                <h1 class="mt-6 font-display text-4xl font-extrabold leading-tight sm:text-6xl lg:text-7xl">
                    <span class="block bg-clip-text text-transparent bg-gradient-to-r from-white/90 via-slate-300 to-amber-200">MaketuShop</span>
                    <span class="block text-2xl sm:text-3xl mt-1 font-semibold text-rose-300">Le marché local réinventé</span>
                </h1>

                <p class="mt-4 max-w-2xl text-base leading-relaxed text-white/70">
                    {{ t('landing.heroSubtitle') }}
                </p>

                <!-- Glass CTA Panel -->
                <div class="mt-8 w-full max-w-3xl rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur-xl shadow-glass">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex-1 text-left">
                            <p class="text-sm text-white/60">Découvrez des artisans locaux et soutenez l'économie de proximité.</p>
                            <div class="mt-3 flex flex-wrap gap-3">
                                <Button variant="mesh" class="h-12 px-6 rounded-lg font-bold" @click="goToProducts">
                                    {{ t('landing.heroBuy') }}
                                    <ArrowRight class="h-4 w-4 ml-2" />
                                </Button>
                                <Button variant="ghost" class="h-12 px-6 rounded-lg border border-white/10 text-white/90" @click="goTo('#cta-split')">
                                    <Store class="h-4 w-4 mr-2" /> {{ t('landing.heroSell') }}
                                </Button>
                            </div>
                        </div>

                        <div class="mt-4 md:mt-0 w-full md:w-80">
                            <div class="rounded-2xl border border-white/6 bg-gradient-to-br from-white/6 to-white/3 p-4 backdrop-blur-sm shadow-inner">
                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <template v-for="stat in stats" :key="stat.label">
                                        <div>
                                            <p class="text-xl font-extrabold">{{ stat.value }}{{ stat.suffix }}</p>
                                            <p class="text-xs uppercase tracking-wide text-white/60">{{ stat.label }}</p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
