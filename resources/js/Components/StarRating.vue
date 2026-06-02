<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    rateableId: { type: Number, default: null },
    rateableType: { type: String, default: null }, // 'product' | 'shop' | 'service'
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
// On ne peut noter que si un type/id est fourni et qu'on n'est pas en lecture seule
const canRate = computed(() => !props.readonly && !!props.rateableType && !!props.rateableId);
const effectiveRating = computed(() => props.userRating || props.averageRating);

const starSizes = { sm: 'h-3 w-3', md: 'h-4 w-4', lg: 'h-5 w-5' };
const starSize = computed(() => starSizes[props.size] || starSizes.md);

const isFilled = (star) => {
    const rating = hovered.value || effectiveRating.value;
    return star <= rating;
};

const routeFor = (type) => {
    if (type === 'product') return 'ratings.product';
    if (type === 'service') return 'ratings.service';
    return 'ratings.shop';
};

const interactive = computed(() => canRate.value && isAuthenticated.value);

const rate = (score) => {
    if (!interactive.value || submitting.value) return;
    submitting.value = true;
    router.post(route(routeFor(props.rateableType), props.rateableId), { score }, {
        preserveScroll: true,
        onFinish: () => { submitting.value = false; },
    });
};
</script>

<template>
    <div class="inline-flex items-center gap-1.5">
        <div class="flex items-center gap-0.5">
            <button
                v-for="star in 5"
                :key="star"
                type="button"
                class="transition-transform"
                :class="[
                    interactive ? 'cursor-pointer hover:scale-110 active:scale-95' : 'cursor-default',
                    submitting && 'opacity-50 pointer-events-none',
                ]"
                @mouseenter="interactive && (hovered = star)"
                @mouseleave="interactive && (hovered = 0)"
                @click="rate(star)"
                :aria-label="`${star} étoile${star > 1 ? 's' : ''}`"
            >
                <Star
                    :class="[
                        starSize,
                        isFilled(star) ? 'fill-amber-400 text-amber-400' : 'fill-none text-muted-foreground/30',
                    ]"
                />
            </button>
        </div>

        <span v-if="ratingsCount > 0" class="text-xs font-semibold text-foreground/70">
            {{ averageRating.toFixed(1) }}
            <span class="font-normal text-muted-foreground">({{ ratingsCount }})</span>
        </span>
        <span v-else-if="interactive" class="text-xs text-muted-foreground">
            {{ t('rating.beFirst') }}
        </span>
    </div>
</template>
