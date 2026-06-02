<script setup>
import TestimonialCard from '@/components/landing/sections/testimonials/TestimonialCard.vue';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

/**
 * @component TestimonialCarousel
 * @description Carousel simple d'avis avec auto-play.
 * @example <TestimonialCarousel :items="testimonials" />
 */
const props = defineProps({
    items: { type: Array, default: () => [] },
});

const index = ref(0);
let timer = null;

const next = () => {
    index.value = (index.value + 1) % props.items.length;
};

const prev = () => {
    index.value = (index.value - 1 + props.items.length) % props.items.length;
};

onMounted(() => {
    timer = setInterval(() => {
        if (props.items.length > 1) next();
    }, 4500);
});

onUnmounted(() => clearInterval(timer));
</script>

<template>
    <div class="space-y-4">
        <TestimonialCard v-if="items.length" :testimonial="items[index]" />
        <div class="flex items-center justify-center gap-2">
            <Button variant="outline" size="icon" aria-label="Témoignage précédent" @click="prev">
                <ChevronLeft class="h-4 w-4" />
            </Button>
            <Button variant="outline" size="icon" aria-label="Témoignage suivant" @click="next">
                <ChevronRight class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>


