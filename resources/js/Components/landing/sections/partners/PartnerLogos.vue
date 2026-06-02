<script setup>
/**
 * @component PartnerLogos
 * @description Liste de logos défilants en continu.
 * @example <PartnerLogos :logos="logos" />
 */
defineProps({
    logos: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-border bg-card/50 p-4">
        <div class="flex w-max animate-scroll gap-10 pr-10">
            <div
                v-for="(logo, idx) in [...logos, ...logos]"
                :key="`${logo.name}-${idx}`"
                class="flex h-12 min-w-[120px] items-center justify-center rounded-lg border border-border bg-background px-4"
            >
                <img :src="logo.src" :alt="logo.name" loading="lazy" class="h-7 w-auto object-contain opacity-80" />
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes scroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.animate-scroll {
    animation: scroll 20s linear infinite;
}
</style>


