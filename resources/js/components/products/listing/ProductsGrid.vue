<script setup>
import EmptyProductsState from '@/components/products/listing/EmptyProductsState.vue';
import ProductCard from '@/components/products/listing/ProductCard.vue';
import ProductCardSkeleton from '@/components/products/listing/ProductCardSkeleton.vue';

defineProps({
    products: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});
</script>

<template>
    <div v-if="loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        <ProductCardSkeleton v-for="index in 8" :key="index" />
    </div>

    <div
        v-else-if="products.length"
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4"
    >
        <ProductCard v-for="product in products" :key="product.id" :product="product" />
    </div>

    <EmptyProductsState v-else />
</template>

