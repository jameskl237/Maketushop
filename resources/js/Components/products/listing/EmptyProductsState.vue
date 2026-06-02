<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <div class="rounded-xl border border-dashed border-border p-10 text-center">
        <h3 class="text-lg font-semibold">{{ t('productsPage.emptyTitle') }}</h3>
        <p class="mt-2 text-sm text-muted-foreground">
            {{ t('productsPage.emptyDescription') }}
        </p>
    </div>
</template>

