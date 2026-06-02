<script setup>
import { Button } from '@/components/ui/button';
import { useTheme } from '@/composables/useTheme';
import { Moon, Sun } from 'lucide-vue-next';

defineProps({
    floating: {
        type: Boolean,
        default: true,
    },
});

const { isDark, toggleTheme } = useTheme();
</script>

<template>
    <Button
        variant="outline"
        size="icon"
        class="border-border/70 bg-card/85 backdrop-blur"
        :class="floating ? 'fixed right-3 top-3 z-50 sm:right-4 sm:top-4' : ''"
        :aria-label="isDark ? 'Passer en mode clair' : 'Passer en mode sombre'"
        @click="toggleTheme"
    >
        <Sun v-if="isDark" class="h-4 w-4" />
        <Moon v-else class="h-4 w-4" />
    </Button>
</template>

