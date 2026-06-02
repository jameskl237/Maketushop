<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});
</script>

<template>
  <section class="pt-6">
    <div class="mb-3 flex items-center justify-between px-4">
      <h2 class="font-display text-[15px] font-black">Catégories</h2>
      <Link href="/products" class="text-[11px] font-bold text-primary">Tout voir</Link>
    </div>

    <div class="flex gap-2 overflow-x-auto px-4 pb-1 no-scrollbar">
      <button v-for="cat in props.categories" :key="cat.id" class="group relative flex-shrink-0 w-[88px] overflow-hidden rounded-2xl active:scale-95 transition-transform">
        <div class="relative h-[88px] w-[88px]">
          <img :src="cat.image || '/images/Maketu1.png'" :alt="cat.name" class="h-full w-full object-cover" loading="lazy" />
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent" />
          <p class="absolute bottom-2 left-0 right-0 text-center text-[9px] font-black leading-tight text-white px-1">{{ cat.name }}</p>
        </div>
      </button>
    </div>
  </section>
</template>
