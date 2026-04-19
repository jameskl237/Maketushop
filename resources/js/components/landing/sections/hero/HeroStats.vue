<script setup>
import AnimatedCounter from '@/components/landing/utils/AnimatedCounter.vue';
import ScrollReveal from '@/components/landing/utils/ScrollReveal.vue';

defineProps({
    stats: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-3">
        <ScrollReveal
            v-for="(stat, idx) in stats"
            :key="stat.label"
            :delay="idx * 100"
        >
            <div class="group relative overflow-hidden rounded-2xl border border-border/60 bg-card/70 px-6 py-5 shadow-sm backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                <!-- Accent line top -->
                <div class="absolute inset-x-0 top-0 h-0.5 rounded-t-2xl bg-gradient-to-r from-transparent via-primary/50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" />

                <p class="font-display text-3xl font-semibold text-foreground">
                    <AnimatedCounter :value="stat.value" :suffix="stat.suffix" />
                </p>
                <p class="mt-1.5 text-[12px] font-medium uppercase tracking-widest text-muted-foreground">
                    {{ stat.label }}
                </p>
            </div>
        </ScrollReveal>
    </div>
</template>
