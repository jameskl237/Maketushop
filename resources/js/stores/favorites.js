import { router, usePage } from '@inertiajs/vue3';
import { defineStore } from 'pinia';

export const useFavoritesStore = defineStore('favorites', {
    state: () => ({
        productIds: [],
        hydrated: false,
    }),
    getters: {
        has: (state) => (productId) => state.productIds.includes(productId),
    },
    actions: {
        /**
         * Hydrate depuis les IDs partagés par Inertia (auth.favorite_product_ids).
         * Idempotent : on resynchronise à chaque navigation.
         */
        hydrate() {
            const page = usePage();
            const ids = page.props?.auth?.favorite_product_ids ?? [];
            this.productIds = [...ids];
            this.hydrated = true;
        },

        isAuthenticated() {
            return !!usePage().props?.auth?.user;
        },

        /**
         * Bascule un produit en favori. Met à jour l'état localement (optimiste)
         * puis persiste côté serveur. Retourne le nouvel état (true = favori).
         */
        toggleProduct(productId) {
            const wasFavorite = this.productIds.includes(productId);

            if (wasFavorite) {
                this.productIds = this.productIds.filter((id) => id !== productId);
            } else {
                this.productIds.push(productId);
            }

            router.post(
                route('favorites.toggle'),
                { type: 'product', id: productId },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onError: () => {
                        // rollback en cas d'échec
                        if (wasFavorite) {
                            this.productIds.push(productId);
                        } else {
                            this.productIds = this.productIds.filter((id) => id !== productId);
                        }
                    },
                },
            );

            return !wasFavorite;
        },
    },
});
