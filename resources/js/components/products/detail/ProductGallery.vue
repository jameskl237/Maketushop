<script setup>
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    images: { type: Array, default: () => [] },
    fallbackImage: { type: String, default: '/images/Maketu1.png' },
});
const { t } = useI18n();

const activeIndex = ref(0);

watch(
    () => props.images,
    () => {
        activeIndex.value = 0;
    },
);

const hasImages = computed(() => props.images.length > 0);
const currentImage = computed(() =>
    hasImages.value ? props.images[activeIndex.value]?.url : props.fallbackImage,
);

const prev = () => {
    if (!hasImages.value) return;
    activeIndex.value = (activeIndex.value - 1 + props.images.length) % props.images.length;
};

const next = () => {
    if (!hasImages.value) return;
    activeIndex.value = (activeIndex.value + 1) % props.images.length;
};
</script>

<template>
    <div class="space-y-3">
        <div class="group relative aspect-square overflow-hidden rounded-xl border bg-muted">
            <img :src="currentImage" :alt="t('public.products')" class="h-full w-full object-cover" />
            <Button
                type="button"
                variant="secondary"
                size="icon"
                class="absolute left-2 top-1/2 h-9 w-9 -translate-y-1/2 opacity-0 transition-opacity group-hover:opacity-100"
                @click="prev"
            >
                <ChevronLeft class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="secondary"
                size="icon"
                class="absolute right-2 top-1/2 h-9 w-9 -translate-y-1/2 opacity-0 transition-opacity group-hover:opacity-100"
                @click="next"
            >
                <ChevronRight class="h-4 w-4" />
            </Button>
        </div>

        <div class="grid grid-cols-5 gap-2">
            <button
                v-for="(image, index) in images"
                :key="image.id"
                type="button"
                class="aspect-square overflow-hidden rounded-md border"
                :class="activeIndex === index ? 'border-primary' : 'border-border'"
                @click="activeIndex = index"
            >
                <img :src="image.url" :alt="image.alt || t('public.products')" class="h-full w-full object-cover" />
            </button>
        </div>
    </div>
</template>

