<script setup>
defineProps({
    step: { type: Object, required: true },
    index: { type: Number, required: true },
});
</script>

<template>
    <div class="group relative rounded-[32px] border border-white/20 bg-glass/30 p-8 backdrop-blur-xl transition-all duration-500 hover:shadow-2xl hover:shadow-pink/5 hover:-translate-y-2 dark:bg-white/5">
        <!-- Oversized Background Number -->
        <div class="absolute -right-4 -top-8 select-none font-display text-[120px] font-black text-primary/5 transition-colors group-hover:text-primary/10">
            0{{ index }}
        </div>

        <div class="relative z-10">
            <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-mesh-gradient text-white shadow-lg shadow-primary/20">
                <span class="font-display text-lg font-black">{{ index }}</span>
            </div>

            <h3 class="font-display text-[22px] font-black leading-tight text-foreground transition-colors group-hover:text-primary">
                {{ step.title }}
            </h3>
            <p class="mt-4 text-[15px] leading-relaxed text-muted-foreground/80">
                {{ step.description }}
            </p>
        </div>

        <!-- Progress Connector (mobile only) -->
        <div class="mt-6 flex h-1 w-12 rounded-full bg-gradient-to-r from-primary to-transparent md:hidden"></div>
    </div>
</template>
