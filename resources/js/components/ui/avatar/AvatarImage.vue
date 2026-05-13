<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
    src: { type: String, default: '' },
    alt: { type: String, default: '' },
    class: { type: null, required: false },
});
</script>

<template>
    <img v-if="src" :src="src" :alt="alt" :class="cn('aspect-square h-full w-full object-cover', props.class)" loading="lazy" />
</template>
