<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
    orientation: { type: String, default: 'horizontal' },
    class: { type: null, required: false },
});
</script>

<template>
    <div
        role="separator"
        :aria-orientation="orientation"
        :class="cn(orientation === 'vertical' ? 'h-full w-px' : 'h-px w-full', 'shrink-0 bg-border', props.class)"
    />
</template>
