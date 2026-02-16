<script setup>
import { Star } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    rating: { type: Number, default: 0 },
    reviewsCount: { type: Number, default: 0 },
});

const stars = computed(() => {
    const rounded = Math.round(props.rating);
    return Array.from({ length: 5 }, (_, idx) => idx < rounded);
});
</script>

<template>
    <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
        <div class="flex items-center gap-0.5">
            <Star
                v-for="(isFilled, index) in stars"
                :key="index"
                class="h-3.5 w-3.5"
                :class="isFilled ? 'fill-amber-400 text-amber-400' : 'text-muted'"
            />
        </div>
        <span>{{ rating.toFixed(1) }}</span>
        <span>({{ reviewsCount }})</span>
    </div>
</template>

