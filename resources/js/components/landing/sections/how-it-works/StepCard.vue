<script setup>
defineProps({
    step: { type: Object, required: true },
    index: { type: Number, required: true },
});
</script>

<template>
    <div class="group relative overflow-hidden rounded-2xl border border-border/60 bg-card p-7 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
        <div class="mb-5 flex items-center gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-primary/30 bg-primary/6 font-display text-lg font-semibold text-primary transition-all duration-300 group-hover:bg-primary group-hover:text-primary-foreground">
                {{ index }}
            </div>
            <div class="h-px flex-1 bg-gradient-to-r from-border to-transparent" />
        </div>
        <h3 class="font-display mb-2.5 text-xl font-semibold text-foreground">{{ step.title }}</h3>
        <p class="text-[13px] leading-relaxed text-muted-foreground">{{ step.description }}</p>
    </div>
</template>
