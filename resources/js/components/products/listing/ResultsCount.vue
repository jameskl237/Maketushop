<script setup>
defineProps({
    total: { type: Number, default: 0 },
});
</script>

<template>
    <p class="text-sm text-muted-foreground">{{ total }} resultat(s)</p>
</template>

