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
    <div class="relative">
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
            :model-value="modelValue"
            class="h-11 pl-9 pr-20"
            :placeholder="t('productsPage.searchPlaceholder')"
            @update:model-value="emit('update:modelValue', $event)"
        />
        <div class="absolute right-2 top-1/2 flex -translate-y-1/2 items-center gap-1">
            <Loader2 v-if="isSearching" class="h-4 w-4 animate-spin text-muted-foreground" />
            <Button v-else-if="modelValue" type="button" variant="ghost" size="icon" class="h-7 w-7" @click="emit('clear')">
                <X class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>

