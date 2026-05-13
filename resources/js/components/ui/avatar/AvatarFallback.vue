<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
    class: { type: null, required: false },
});
</script>

<template>
    <div :class="cn('flex h-full w-full items-center justify-center rounded-full bg-muted', props.class)">
        <slot />
    </div>
</template>
