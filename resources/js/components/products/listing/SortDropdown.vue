<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
    modelValue: { type: String, default: 'newest' },
});

const emit = defineEmits(['update:modelValue']);
const { t } = useI18n();
</script>

<template>
    <select
        :value="modelValue"
        class="h-10 rounded-md border border-input bg-background px-3 text-sm"
        @change="emit('update:modelValue', $event.target.value)"
    >
        <option value="newest">{{ t('productsPage.sortNewest') }}</option>
        <option value="price_asc">{{ t('productsPage.sortPriceAsc') }}</option>
        <option value="price_desc">{{ t('productsPage.sortPriceDesc') }}</option>
        <option value="popular">{{ t('productsPage.sortPopular') }}</option>
    </select>
</template>

