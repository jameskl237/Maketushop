<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
    category: { type: Object, required: true },
});

const { t, locale } = useI18n();
</script>

<template>
    <div class="group relative aspect-[4/5] overflow-hidden rounded-[28px] border-none bg-slate-100 shadow-sm transition-all duration-500 hover:shadow-2xl active:scale-95">
        <!-- Background Image -->
        <img
            :src="category.image"
            :alt="category.name"
            loading="lazy"
            class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
        />

        <!-- Animated Overlay Gradient -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent transition-opacity group-hover:opacity-90"></div>

        <!-- Glass Content Panel -->
        <div class="absolute bottom-4 left-4 right-4 flex flex-col gap-1 rounded-[22px] border border-white/20 bg-white/20 p-4 backdrop-blur-xl transition-all duration-300 group-hover:bottom-6">
            <h3 class="font-display text-[17px] font-black tracking-tight text-white">{{ category.name }}</h3>
            <div class="flex items-center justify-between">
                <p class="text-[10px] font-bold uppercase tracking-widest text-white/60">
                    {{ category.count.toLocaleString(locale === 'en' ? 'en-US' : 'fr-FR') }} items
                </p>
                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-black shadow-lg">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Inner Shine Effect -->
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-tr from-white/10 to-transparent opacity-0 transition-opacity group-hover:opacity-100"></div>
    </div>
</template>
