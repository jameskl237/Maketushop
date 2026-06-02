<script setup>
import StarRating from '@/components/StarRating.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { router, usePage } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    // 'product' | 'service' | 'shop'
    rateableType: { type: String, required: true },
    rateableId: { type: Number, required: true },
    reviews: { type: Array, default: () => [] },
    averageRating: { type: Number, default: 0 },
    ratingsCount: { type: Number, default: 0 },
    userRating: { type: Number, default: null },
});

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

// Login en gardant le retour sur la page courante (puis l'utilisateur peut noter)
const loginUrl = computed(() => {
    const current = typeof window !== 'undefined' ? window.location.pathname + window.location.search : '/';
    return `${route('login')}?redirect=${encodeURIComponent(current)}`;
});

const comment = ref('');
const score = ref(props.userRating || 0);
const submitting = ref(false);

const routeName = computed(() => {
    if (props.rateableType === 'service') return 'ratings.service';
    if (props.rateableType === 'shop') return 'ratings.shop';
    return 'ratings.product';
});

const formatDate = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
};

const submit = () => {
    if (!score.value || submitting.value) return;
    submitting.value = true;
    router.post(
        route(routeName.value, props.rateableId),
        { score: score.value, comment: comment.value || null },
        {
            preserveScroll: true,
            onSuccess: () => { comment.value = ''; },
            onFinish: () => { submitting.value = false; },
        },
    );
};
</script>

<template>
    <section class="mt-2 bg-white px-4 py-4 dark:bg-card">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-[16px] font-bold">Avis ({{ ratingsCount }})</h2>
            <div class="flex items-center gap-1 text-[13px] font-bold text-shop-amber">
                <Star class="h-4 w-4 fill-current" />
                {{ (Number(averageRating) || 0).toFixed(1) }}
            </div>
        </div>

        <!-- Formulaire d'avis (client connecté) -->
        <div v-if="isAuthenticated" class="mt-3 rounded-2xl border border-border p-3">
            <p class="mb-2 text-[12px] font-semibold text-foreground">
                {{ userRating ? 'Modifier votre note' : 'Donnez votre avis' }}
            </p>
            <div class="mb-2 flex items-center gap-1">
                <button
                    v-for="star in 5"
                    :key="star"
                    type="button"
                    class="transition-transform active:scale-90"
                    @click="score = star"
                >
                    <Star
                        class="h-7 w-7"
                        :class="star <= score ? 'fill-amber-400 text-amber-400' : 'fill-none text-muted-foreground/30'"
                    />
                </button>
            </div>
            <Textarea
                v-model="comment"
                rows="2"
                placeholder="Partagez votre expérience (optionnel)..."
                class="text-[13px]"
            />
            <Button
                :disabled="!score || submitting"
                class="mt-2 h-9 w-full rounded-xl text-[12px] font-bold"
                @click="submit"
            >
                {{ submitting ? 'Envoi...' : 'Publier mon avis' }}
            </Button>
        </div>

        <!-- Invitation à se connecter -->
        <div v-else class="mt-3 rounded-2xl border border-dashed border-border p-3 text-center">
            <p class="text-[12px] text-muted-foreground">Connectez-vous pour laisser un avis.</p>
            <a :href="loginUrl" class="mt-2 inline-block">
                <Button variant="outline" size="sm" class="rounded-xl text-[12px]">Se connecter</Button>
            </a>
        </div>

        <!-- Liste des avis -->
        <div v-if="reviews.length" class="mt-4 space-y-3">
            <div v-for="review in reviews" :key="review.id" class="border-t border-border pt-3">
                <div class="flex items-center justify-between">
                    <p class="text-[13px] font-semibold text-foreground">{{ review.author }}</p>
                    <StarRating :average-rating="review.score" :ratings-count="0" readonly size="sm" />
                </div>
                <p v-if="review.comment" class="mt-1 text-[12px] leading-5 text-shop-muted">{{ review.comment }}</p>
                <p class="mt-1 text-[10px] text-shop-light">{{ formatDate(review.created_at) }}</p>
            </div>
        </div>
        <p v-else class="mt-4 text-center text-[12px] text-muted-foreground">
            Aucun avis pour le moment. Soyez le premier !
        </p>
    </section>
</template>
