<script setup>
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from 'vue-i18n';

/**
 * @component CategoryCard
 * @description Carte catégorie marketplace.
 * @example <CategoryCard :category="cat" />
 */
defineProps({
    category: { type: Object, required: true },
});

const { t, locale } = useI18n();
</script>

<template>
    <Card class="overflow-hidden border-border/70 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
        <img
            :src="category.image"
            :alt="`Illustration catégorie ${category.name}`"
            loading="lazy"
            class="h-32 w-full object-cover"
        />
        <CardContent class="flex items-center justify-between p-4">
            <div>
                <h3 class="text-base font-semibold">{{ category.name }}</h3>
                <p class="text-xs text-muted-foreground">
                    {{ category.count.toLocaleString(locale === 'en' ? 'en-US' : 'fr-FR') }} {{ t('landing.productsCount') }}
                </p>
            </div>
            <Badge variant="secondary">{{ t('landing.popular') }}</Badge>
        </CardContent>
    </Card>
</template>


