<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    rateableId: { type: Number, required: true },
    rateableType: { type: String, required: true }, // 'product' or 'shop'
    averageRating: { type: Number, default: 0 },
    ratingsCount: { type: Number, default: 0 },
    userRating: { type: Number, default: null },
    readonly: { type: Boolean, default: false },
    size: { type: String, default: 'md' }, // sm | md | lg
});

const { t } = useI18n();
const page = usePage();
const hovered = ref(0);
const submitting = ref(false);

const isAuthenticated = computed(() => !!page.props.auth?.user);
const effectiveRating = computed(() => props.userRating || props.averageRating);

const starSizes = { sm: 'h-3 w-3', md: 'h-4 w-4', lg: 'h-5 w-5' };
const starSize = computed(() => starSizes[props.size] || starSizes.md);

const isFilled = (star) => {
    const rating = hovered.value || effectiveRating.value;
    return star <= rating;
};

const isHalf = (star) => {
    const rating = hovered.value || effectiveRating.value;
    return !hovered.value && star - 0.5 <= rating && star > rating;
};

const rate = (score) => {
    if (props.readonly || !isAuthenticated.value || submitting.value) return;
    submitting.value = true;
    const routeName = props.rateableType === 'product' ? 'ratings.product' : 'ratings.shop';
    router.post(route(routeName, props.rateableId), { score }, {
        preserveScroll: true,
        onFinish: () => { submitting.value = false; },
    });
};
</script>

<template>
    <div class="inline-flex items-center gap-1.5">
        <!-- Stars -->
        <div class="flex items-center gap-0.5">
            <button
                v-for="star in 5"
                :key="star"
                type="button"
                class="transition-transform"
                :class="[
                    readonly || !isAuthenticated ? 'cursor-default' : 'cursor-pointer hover:scale-110 active:scale-95',
                    submitting && 'opacity-50 pointer-events-none'
                ]"
                @mouseenter="!readonly && isAuthenticated && (hovered = star)"
                @mouseleave="!readonly && isAuthenticated && (hovered = 0)"
                @click="rate(star)"
                :aria-label="`${star} étoile${star > 1 ? 's' : ''}`"
            >
                <Star
                    :class="[
                        starSize,
                        isFilled(star) ? 'fill-amber-400 text-amber-400' : 'fill-none text-muted-foreground/30'
                    ]"
                />
            </button>
        </div>

        <!-- Score + count -->
        <span v-if="ratingsCount > 0" class="text-xs font-semibold text-foreground/70">
            {{ averageRating.toFixed(1) }}
            <span class="font-normal text-muted-foreground">({{ ratingsCount }})</span>
        </span>
        <span v-else-if="!readonly && isAuthenticated" class="text-xs text-muted-foreground">
            {{ t('rating.beFirst') }}
        </span>
    </div>
</template>
