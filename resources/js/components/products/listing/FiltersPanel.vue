<script setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useI18n } from 'vue-i18n';

defineProps({
    categories: { type: Array, default: () => [] },
    locations: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    hasActiveFilters: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle-category', 'toggle-location', 'reset']);
const { t } = useI18n();
</script>

<template>
    <div class="space-y-6">
        <section class="space-y-3">
            <h3 class="text-sm font-semibold">{{ t('productsPage.filterCategories') }}</h3>
            <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                <label
                    v-for="category in categories"
                    :key="category.id"
                    class="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground"
                >
                    <input
                        type="checkbox"
                        class="h-4 w-4 rounded border-input"
                        :checked="filters.categories.includes(category.id)"
                        @change="emit('toggle-category', category.id)"
                    />
                    <span>{{ category.name }}</span>
                    <span class="text-xs">({{ category.products_count }})</span>
                </label>
            </div>
        </section>

        <section class="space-y-3">
            <h3 class="text-sm font-semibold">{{ t('public.price') }}</h3>
            <div class="grid grid-cols-2 gap-2">
                <Input
                    :model-value="filters.price_min"
                    type="number"
                    min="0"
                    :placeholder="t('public.min')"
                    @update:model-value="filters.price_min = $event"
                />
                <Input
                    :model-value="filters.price_max"
                    type="number"
                    min="0"
                    :placeholder="t('public.max')"
                    @update:model-value="filters.price_max = $event"
                />
            </div>
        </section>

        <section class="space-y-3">
            <h3 class="text-sm font-semibold">{{ t('shopShow.location') }}</h3>
            <div class="max-h-44 space-y-2 overflow-y-auto pr-1">
                <label
                    v-for="location in locations"
                    :key="location"
                    class="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground"
                >
                    <input
                        type="checkbox"
                        class="h-4 w-4 rounded border-input"
                        :checked="filters.locations.includes(location)"
                        @change="emit('toggle-location', location)"
                    />
                    <span>{{ location }}</span>
                </label>
            </div>
        </section>

        <section class="space-y-2">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground">
                <input
                    v-model="filters.in_stock"
                    type="checkbox"
                    class="h-4 w-4 rounded border-input"
                />
                <span>{{ t('public.inStockOnly') }}</span>
            </label>
        </section>

        <Button v-if="hasActiveFilters" type="button" variant="outline" class="w-full" @click="emit('reset')">
            {{ t('productsPage.resetFilters') }}
        </Button>
    </div>
</template>

