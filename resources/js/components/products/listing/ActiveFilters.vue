<script setup>
import { Button } from '@/components/ui/button';

defineProps({
    filters: { type: Object, required: true },
    categoryNameMap: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['remove', 'reset']);
</script>

<template>
    <div v-if="filters.length" class="flex flex-wrap items-center gap-2">
        <button
            v-for="filter in filters"
            :key="filter.key"
            type="button"
            class="inline-flex items-center gap-2 rounded-full border border-border bg-background px-3 py-1 text-xs text-foreground hover:bg-accent"
            @click="emit('remove', filter)"
        >
            <span>{{ filter.label }}</span>
            <span class="text-muted-foreground">x</span>
        </button>
        <Button type="button" variant="ghost" size="sm" class="h-8 px-3 text-xs" @click="emit('reset')">
            Reinitialiser
        </Button>
    </div>
</template>

