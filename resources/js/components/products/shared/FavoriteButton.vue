<script setup>
import { Button } from '@/components/ui/button';
import { useFavoritesStore } from '@/stores/favorites';
import { Heart } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    productId: { type: Number, required: true },
});

const favoritesStore = useFavoritesStore();
const isFavorite = computed(() => favoritesStore.has(props.productId));

const toggleFavorite = () => favoritesStore.toggle(props.productId);
</script>

<template>
    <Button
        type="button"
        variant="secondary"
        size="icon"
        class="h-8 w-8 rounded-full bg-background/95 shadow-sm"
        @click.stop="toggleFavorite"
    >
        <Heart class="h-4 w-4" :class="isFavorite ? 'fill-primary text-primary' : 'text-muted-foreground'" />
    </Button>
</template>

