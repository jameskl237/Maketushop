<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentPrice: { type: Number, required: true },
    originalPrice: { type: Number, default: null },
    discountPercentage: { type: Number, default: null },
    large: { type: Boolean, default: false },
});

const formatPrice = (value) =>
    new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XAF',
        maximumFractionDigits: 0,
    }).format(value);

const hasDiscount = computed(() => props.originalPrice && props.originalPrice > props.currentPrice);
</script>

<template>
    <div class="flex items-center gap-2">
        <span :class="[large ? 'text-2xl' : 'text-base', 'font-bold text-primary']">
            {{ formatPrice(currentPrice) }}
        </span>
        <span v-if="hasDiscount" class="text-sm text-muted-foreground line-through">
            {{ formatPrice(originalPrice) }}
        </span>
        <span
            v-if="discountPercentage"
            class="rounded-full bg-destructive/10 px-2 py-0.5 text-xs font-semibold text-destructive"
        >
            -{{ discountPercentage }}%
        </span>
    </div>
</template>

