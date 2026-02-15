<script setup>
/**
 * @component GradientBlob
 * @description Blob décoratif animé en arrière-plan.
 * @example <GradientBlob class="left-0 top-0" />
 */
defineProps({
    class: { type: String, default: '' },
});
</script>

<template>
    <div
        aria-hidden="true"
        class="pointer-events-none absolute h-56 w-56 rounded-full bg-primary/25 blur-3xl animate-blob"
        :class="$props.class"
    />
</template>


