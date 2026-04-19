<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
    category: { type: Object, required: true },
});

const { t, locale } = useI18n();
</script>

<template>
    <div class="group relative overflow-hidden rounded-2xl border border-border/60 bg-card shadow-sm transition-all duration-400 hover:-translate-y-1.5 hover:shadow-[0_16px_40px_-8px_hsl(var(--primary)/0.15)]">
        <!-- Image with overlay -->
        <div class="relative h-40 overflow-hidden">
            <img
                :src="category.image"
                :alt="`Illustration catégorie ${category.name}`"
                loading="lazy"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-foreground/30 via-transparent to-transparent" />
        </div>

        <!-- Content -->
        <div class="flex items-center justify-between px-5 py-4">
            <div>
                <h3 class="font-display text-[16px] font-semibold text-foreground">{{ category.name }}</h3>
                <p class="mt-0.5 text-[11px] font-medium uppercase tracking-wider text-muted-foreground">
                    {{ category.count.toLocaleString(locale === 'en' ? 'en-US' : 'fr-FR') }} {{ t('landing.productsCount') }}
                </p>
            </div>
            <div class="flex h-8 w-8 items-center justify-center rounded-full border border-border/60 bg-muted text-muted-foreground transition-all duration-200 group-hover:border-primary/40 group-hover:bg-primary/8 group-hover:text-primary">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </div>
    </div>
</template>
