<script setup>
import { Button } from '@/components/ui/button';
import { useFavoritesStore } from '@/stores/favorites';
import { router } from '@inertiajs/vue3';
import { Heart } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

const props = defineProps({
    productId: { type: Number, required: true },
});

const favoritesStore = useFavoritesStore();
const isFavorite = computed(() => favoritesStore.has(props.productId));

onMounted(() => {
    if (!favoritesStore.hydrated) {
        favoritesStore.hydrate();
    }
});

const toggleFavorite = () => {
    if (!favoritesStore.isAuthenticated()) {
        // Invité : on l'envoie vers le login avant de pouvoir favoriser
        router.visit(route('login'));
        return;
    }

    favoritesStore.toggleProduct(props.productId);
};
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
