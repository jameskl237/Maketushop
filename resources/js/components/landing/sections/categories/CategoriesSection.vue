<script setup>
import CategoryCard from '@/components/landing/sections/categories/CategoryCard.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';

/**
 * @component CategoriesSection
 * @description Prévisualisation des catégories.
 * @example <CategoriesSection :categories="categories" />
 */
defineProps({
    categories: { type: Array, default: () => [] },
});
</script>

<template>
    <section id="categories" class="py-16 sm:py-20">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <ScrollReveal>
                <div class="text-center">
                    <h2 class="text-3xl font-bold">Catégories populaires</h2>
                    <p class="mt-3 text-muted-foreground">Explorez rapidement ce que recherchent vos clients.</p>
                </div>
            </ScrollReveal>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <ScrollReveal
                    v-for="(category, idx) in categories"
                    :key="category.id"
                    :delay="idx * 70"
                >
                    <CategoryCard :category="category" />
                </ScrollReveal>
            </div>
        </div>
    </section>
</template>


