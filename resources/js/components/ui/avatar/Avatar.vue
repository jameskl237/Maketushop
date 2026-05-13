<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
    class: { type: null, required: false },
});
</script>

<template>
    <div :class="cn('relative flex shrink-0 overflow-hidden rounded-full', props.class)">
        <slot />
    </div>
</template>
