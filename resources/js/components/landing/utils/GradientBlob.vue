<script setup>
defineProps({
    class: { type: String, default: '' },
});
</script>

<template>
    <div
        aria-hidden="true"
        class="pointer-events-none absolute h-56 w-56 rounded-full bg-primary/20 blur-[80px] animate-blob"
        :class="$props.class"
    />
</template>
