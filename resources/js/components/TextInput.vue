<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-md border border-input bg-background text-foreground shadow-sm placeholder:text-muted-foreground focus:border-ring focus:ring-ring"
        v-model="model"
        ref="input"
    />
</template>
