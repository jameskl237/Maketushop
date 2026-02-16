<script setup>
const props = defineProps({
    stock: { type: Number, default: 0 },
});
</script>

<template>
    <span
        class="rounded-full px-2 py-0.5 text-xs font-medium"
        :class="
            stock <= 0
                ? 'bg-red-500/10 text-red-600'
                : stock <= 10
                  ? 'bg-orange-500/10 text-orange-600'
                  : 'bg-green-500/10 text-green-600'
        "
    >
        {{
            stock <= 0 ? 'Rupture' : stock <= 10 ? `Stock faible (${stock})` : `En stock (${stock})`
        }}
    </span>
</template>

