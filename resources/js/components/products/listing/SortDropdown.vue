<script setup>
defineProps({
    modelValue: { type: String, default: 'newest' },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <select
        :value="modelValue"
        class="h-10 rounded-md border border-input bg-background px-3 text-sm"
        @change="emit('update:modelValue', $event.target.value)"
    >
        <option value="newest">Nouveautes</option>
        <option value="price_asc">Prix croissant</option>
        <option value="price_desc">Prix decroissant</option>
        <option value="popular">Popularite</option>
    </select>
</template>

