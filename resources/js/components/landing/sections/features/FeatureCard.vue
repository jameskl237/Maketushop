<script setup>
defineProps({
    feature: { type: Object, required: true },
});
</script>

<template>
    <div class="group relative overflow-hidden rounded-2xl border border-border/60 bg-card p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_12px_32px_-8px_hsl(var(--primary)/0.12)]">
        <!-- Hover accent -->
        <div class="absolute inset-x-0 bottom-0 h-0.5 rounded-b-2xl bg-gradient-to-r from-transparent via-primary/60 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" />

        <div class="mb-5 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-primary/8 text-primary ring-1 ring-primary/15 transition-all duration-300 group-hover:bg-primary/15 group-hover:ring-primary/25">
            <component :is="feature.icon" class="h-5 w-5" />
        </div>

        <h3 class="font-display mb-2 text-xl font-semibold text-foreground">{{ feature.title }}</h3>
        <p class="text-[13px] leading-relaxed text-muted-foreground">{{ feature.description }}</p>
    </div>
</template>
