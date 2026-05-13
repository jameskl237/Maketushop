<script setup>
import TopBar from '@/components/shop/TopBar.vue';

defineProps({
    navItems: { type: Array, default: () => [] },
});
</script>

<template>
    <TopBar show-back-home />
</template>
