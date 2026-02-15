<script setup>
import { Button } from '@/components/ui/button';
import { getI18nLocale, setI18nLocale } from '@/i18n';
import { Languages } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    floating: {
        type: Boolean,
        default: true,
    },
});

const locale = ref(getI18nLocale());

const toggleLocale = () => {
    const nextLocale = locale.value === 'fr' ? 'en' : 'fr';
    setI18nLocale(nextLocale);
    locale.value = nextLocale;
};
</script>

<template>
    <Button
        variant="outline"
        size="sm"
        class="border-border/70 bg-card/85 backdrop-blur"
        :class="floating ? 'fixed right-14 top-3 z-50 sm:right-16 sm:top-4' : ''"
        :aria-label="$t('language.toggle')"
        @click="toggleLocale"
    >
        <Languages class="mr-2 h-4 w-4" />
        {{ locale.toUpperCase() }}
    </Button>
</template>


