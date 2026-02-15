<script setup>
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

/**
 * @component CategoryCard
 * @description Carte catégorie marketplace.
 * @example <CategoryCard :category="cat" />
 */
defineProps({
    category: { type: Object, required: true },
});
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
                <p class="text-xs text-muted-foreground">{{ category.count.toLocaleString('fr-FR') }} produits</p>
            </div>
            <Badge variant="secondary">Populaire</Badge>
        </CardContent>
    </Card>
</template>


