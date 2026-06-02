<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
    total: { type: Number, default: 0 },
});
const { t } = useI18n();
</script>

<template>
    <p class="text-sm text-muted-foreground">{{ t('productsPage.results', { count: total }) }}</p>
</template>

