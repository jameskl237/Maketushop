<script setup>
defineProps({
    feature: { type: Object, required: true },
});
</script>

<template>
    <div class="group relative rounded-[32px] border border-white/20 bg-glass/40 p-8 backdrop-blur-xl transition-all duration-500 hover:shadow-2xl hover:shadow-primary/10 hover:-translate-y-2 dark:bg-white/5">
        <!-- Inner Glow -->
        <div class="pointer-events-none absolute inset-0 rounded-[32px] ring-1 ring-inset ring-white/20 opacity-0 transition-opacity group-hover:opacity-100"></div>

        <div
            class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-pink text-white shadow-lg shadow-primary/20 transition-all duration-500 group-hover:scale-110 group-hover:rotate-6"
        >
            <component :is="feature.icon" class="h-7 w-7" />
        </div>
        <h3 class="font-display text-[20px] font-black leading-tight text-foreground transition-colors group-hover:text-primary">
            {{ feature.title }}
        </h3>
        <p class="mt-3 text-[14px] leading-relaxed text-muted-foreground/80">
            {{ feature.description }}
        </p>
    </div>
</template>
