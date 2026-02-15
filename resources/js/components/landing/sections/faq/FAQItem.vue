<script setup>
/**
 * @component FAQItem
 * @description Item FAQ accessible basé sur details/summary.
 * @example <FAQItem :item="faq" />
 */
defineProps({
    item: { type: Object, required: true },
});
</script>

<template>
    <details class="group rounded-lg border border-border bg-card p-4">
        <summary class="cursor-pointer list-none pr-4 text-sm font-semibold text-foreground">
            {{ item.question }}
        </summary>
        <p class="mt-3 text-sm text-muted-foreground">
            {{ item.answer }}
        </p>
    </details>
</template>


