<script setup>
import { Button } from '@/components/ui/button';
import { useFavoritesStore } from '@/stores/favorites';
import { router } from '@inertiajs/vue3';
import { Heart } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    // Rétrocompat : ciblage produit historique
    productId: { type: Number, default: null },
    // Ciblage générique (product | service)
    itemId: { type: Number, default: null },
    type: { type: String, default: 'product' },
});

const favoritesStore = useFavoritesStore();

const resolvedId = computed(() => props.itemId ?? props.productId);
const resolvedType = computed(() => (props.itemId != null ? props.type : 'product'));

const isFavorite = computed(() => favoritesStore.hasFor(resolvedType.value, resolvedId.value));

const favoriteLabel = computed(() =>
    isFavorite.value ? t('productsPage.favoriteButton.remove') : t('productsPage.favoriteButton.add'),
);

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

    favoritesStore.toggle(resolvedType.value, resolvedId.value);
};
</script>

<template>
    <Button
        type="button"
        variant="secondary"
        size="icon"
        class="h-8 w-8 rounded-full bg-background/95 shadow-sm"
        :aria-label="favoriteLabel"
        :title="favoriteLabel"
        @click.stop="toggleFavorite"
    >
        <Heart class="h-4 w-4" :class="isFavorite ? 'fill-primary text-primary' : 'text-muted-foreground'" />
    </Button>
</template>
