<script setup>
import { computed } from 'vue';

defineProps({
  averageRating: { type: Number, default: 0 },
  ratingsCount: { type: Number, default: 0 },
  size: { type: String, default: 'md' }, // sm | md | lg
});

const filled = computed(() => Math.round(props.averageRating || props.average_rating || 0));
</script>

<template>
  <div class="inline-flex items-center gap-1">
    <svg v-for="i in 5" :key="i" class="h-3 w-3 text-amber-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
      <path v-if="i <= Math.round(averageRating)" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
      <path v-else d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" opacity="0.25" />
    </svg>
    <span class="text-[11px] font-semibold text-shop-light">{{ (averageRating || 0).toFixed(1) }}</span>
    <small class="text-[10px] text-muted-foreground">({{ ratingsCount || 0 }})</small>
  </div>
</template>
