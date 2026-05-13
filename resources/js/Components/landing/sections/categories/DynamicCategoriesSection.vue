<script setup>
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const { t } = useI18n();

const CATEGORY_IMAGES = {
    'artisanat': '/images/artisanat.png',
    'maison': '/images/maison.png',
    'mode': '/images/mode.png',
    'beauté': '/images/beaute.png',
    'beaute': '/images/beaute.png',
    'électronique': '/images/electronique.png',
    'electronique': '/images/electronique.png',
    'alimentation': '/images/alimentation.png',
    'sport': '/images/sport.png',
    'accessoires': '/images/accessoire.png',
};

const getCategoryImage = (cat) => {
    if (cat.image) return cat.image;
    const key = (cat.name || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    return CATEGORY_IMAGES[key] || CATEGORY_IMAGES[(cat.name || '').toLowerCase()] || '/images/Maketu1.png';
};

const goToCategory = (categoryId) => {
    router.visit(route('products.index', { categories: [categoryId] }));
};
</script>

<template>
    <section id="categories" class="py-16 sm:py-20">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <ScrollReveal>
                <div class="text-center">
                    <div class="section-label mx-auto mb-3 justify-center">
                        <span>{{ t('landing.categoriesTitle') }}</span>
                    </div>
                    <h2 class="font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                        {{ t('landing.categoriesTitle') }}
                    </h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-muted-foreground">
                        {{ t('landing.categoriesSubtitle') }}
                    </p>
                </div>
            </ScrollReveal>

            <!-- Dynamic grid from DB -->
            <div v-if="categories.length" class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4">
                <ScrollReveal
                    v-for="(category, idx) in categories"
                    :key="category.id"
                    :delay="idx * 55"
                >
                    <button
                        class="group relative w-full aspect-[4/5] overflow-hidden rounded-3xl bg-slate-100 shadow-sm transition-all duration-500 hover:shadow-xl active:scale-95 cursor-pointer"
                        @click="goToCategory(category.id)"
                    >
                        <img
                            :src="getCategoryImage(category)"
                            :alt="category.name"
                            loading="lazy"
                            class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15 to-transparent"></div>

                        <!-- Glass content panel -->
                        <div class="absolute bottom-3 left-3 right-3 rounded-2xl border border-white/20 bg-white/15 p-3 backdrop-blur-xl transition-all duration-300 group-hover:bottom-5">
                            <h3 class="font-display text-[15px] font-black tracking-tight text-white">{{ category.name }}</h3>
                            <div class="flex items-center justify-between mt-1">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-white/60">
                                    {{ (category.products_count || 0).toLocaleString() }} {{ t('landing.categoryItems') }}
                                </p>
                                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-white text-black shadow">
                                    <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-tr from-white/8 to-transparent opacity-0 transition-opacity group-hover:opacity-100"></div>
                    </button>
                </ScrollReveal>
            </div>

            <!-- Fallback skeleton while loading -->
            <div v-else class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4">
                <div
                    v-for="i in 8"
                    :key="i"
                    class="aspect-[4/5] skeleton"
                />
            </div>
        </div>
    </section>
</template>
