<script setup>
import { Button } from '@/components/ui/button';

defineProps({
    modelValue: { type: Number, default: 1 },
    min: { type: Number, default: 1 },
    max: { type: Number, default: 999 },
});

const emit = defineEmits(['update:modelValue']);

const updateValue = (value) => emit('update:modelValue', value);
</script>

<template>
    <div class="inline-flex items-center rounded-md border border-input">
        <Button
            type="button"
            variant="ghost"
            size="icon"
            class="h-10 w-10 rounded-none"
            :disabled="modelValue <= min"
            @click="updateValue(Math.max(min, modelValue - 1))"
        >
            -
        </Button>
        <input
            :value="modelValue"
            type="number"
            class="h-10 w-14 border-x border-input bg-background text-center text-sm outline-none"
            :min="min"
            :max="max"
            @input="updateValue(Number($event.target.value))"
        />
        <Button
            type="button"
            variant="ghost"
            size="icon"
            class="h-10 w-10 rounded-none"
            :disabled="modelValue >= max"
            @click="updateValue(Math.min(max, modelValue + 1))"
        >
            +
        </Button>
    </div>
</template>

