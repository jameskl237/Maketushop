<script setup>
import CategoryCard from '@/components/landing/sections/categories/CategoryCard.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';
import { useI18n } from 'vue-i18n';

defineProps({
    categories: { type: Array, default: () => [] },
});
const { t } = useI18n();
</script>

<template>
    <section id="categories" class="py-20 sm:py-28">
        <div class="mx-auto max-w-7xl space-y-12 px-5 sm:px-8 lg:px-10">
            <ScrollReveal>
                <div class="text-center">
                    <div class="section-label mx-auto mb-3 justify-center">
                        <span>{{ t('landing.categoriesTitle') }}</span>
                    </div>
                    <h2 class="font-display text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">
                        {{ t('landing.categoriesTitle') }}
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-[14px] leading-relaxed text-muted-foreground">
                        {{ t('landing.categoriesSubtitle') }}
                    </p>
                </div>
            </ScrollReveal>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <ScrollReveal
                    v-for="(category, idx) in categories"
                    :key="category.id"
                    :delay="idx * 65"
                >
                    <CategoryCard :category="category" />
                </ScrollReveal>
            </div>
        </div>
    </section>
</template>
