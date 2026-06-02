<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

/**
 * @component ScrollReveal
 * @description Révèle un bloc au scroll avec IntersectionObserver.
 * @example <ScrollReveal><div>Contenu</div></ScrollReveal>
 */
const props = defineProps({
    as: { type: String, default: 'div' },
    threshold: { type: Number, default: 0.2 },
    once: { type: Boolean, default: true },
    delay: { type: Number, default: 0 },
});

const root = ref(null);
const visible = ref(false);
let observer = null;

const styles = computed(() => ({
    transitionDelay: `${props.delay}ms`,
}));

onMounted(() => {
    observer = new IntersectionObserver(
        ([entry]) => {
            if (entry.isIntersecting) {
                visible.value = true;
                if (props.once) observer?.disconnect();
            } else if (!props.once) {
                visible.value = false;
            }
        },
        { threshold: props.threshold },
    );
    if (root.value) observer.observe(root.value);
});

onUnmounted(() => observer?.disconnect());
</script>

<template>
    <component
        :is="as"
        ref="root"
        :style="styles"
        class="transition-all duration-700"
        :class="visible ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
    >
        <slot />
    </component>
</template>


