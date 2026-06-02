<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

/**
 * @component AnimatedCounter
 * @description Anime un compteur numérique quand il devient visible.
 * @example <AnimatedCounter :value="500" suffix="+" />
 */
const props = defineProps({
    value: { type: Number, required: true },
    duration: { type: Number, default: 1400 },
    suffix: { type: String, default: '' },
    prefix: { type: String, default: '' },
});

const element = ref(null);
const current = ref(0);
let observer = null;
let raf = null;

const formatted = computed(() => `${props.prefix}${Math.floor(current.value).toLocaleString('fr-FR')}${props.suffix}`);

const start = () => {
    const startTime = performance.now();
    const animate = (t) => {
        const progress = Math.min((t - startTime) / props.duration, 1);
        current.value = props.value * (1 - (1 - progress) * (1 - progress));
        if (progress < 1) raf = requestAnimationFrame(animate);
    };
    raf = requestAnimationFrame(animate);
};

onMounted(() => {
    observer = new IntersectionObserver(([entry]) => {
        if (entry.isIntersecting) {
            start();
            observer?.disconnect();
        }
    }, { threshold: 0.4 });

    if (element.value) observer.observe(element.value);
});

onUnmounted(() => {
    observer?.disconnect();
    if (raf) cancelAnimationFrame(raf);
});
</script>

<template>
    <span ref="element">{{ formatted }}</span>
</template>


