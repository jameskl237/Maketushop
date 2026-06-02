<script setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Loader2, Search, X } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

defineProps({
    modelValue: { type: String, default: '' },
    isSearching: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'clear']);
const { t } = useI18n();
</script>

<template>
    <div class="group relative">
        <div class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 transition-colors group-focus-within:text-primary">
            <Search class="h-4.5 w-4.5 text-muted-foreground/60" />
        </div>
        <Input
            :model-value="modelValue"
            class="h-12 w-full rounded-2xl border-white/20 bg-glass px-11 text-[14px] font-medium placeholder:text-muted-foreground/50 backdrop-blur-xl shadow-sm transition-all duration-300 focus-visible:border-primary/30 focus-visible:bg-white/80 focus-visible:ring-0 focus-visible:shadow-xl focus-visible:shadow-primary/5 dark:focus-visible:bg-white/10"
            :placeholder="t('productsPage.searchPlaceholder')"
            @update:model-value="emit('update:modelValue', $event)"
        />
        <div class="absolute right-3 top-1/2 flex -translate-y-1/2 items-center gap-2">
            <Loader2 v-if="isSearching" class="h-5 w-5 animate-spin text-primary" />
            <Button
                v-else-if="modelValue"
                type="button"
                variant="glass"
                size="icon"
                class="h-8 w-8 rounded-xl bg-white/50 shadow-none hover:bg-white"
                @click="emit('clear')"
            >
                <X class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>

