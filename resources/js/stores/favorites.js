import { router, usePage } from '@inertiajs/vue3';
import { defineStore } from 'pinia';

export const useFavoritesStore = defineStore('favorites', {
    state: () => ({
        productIds: [],
        serviceIds: [],
        hydrated: false,
    }),
    getters: {
        has: (state) => (productId) => state.productIds.includes(productId),
        hasService: (state) => (serviceId) => state.serviceIds.includes(serviceId),
    },
    actions: {
        /**
         * Hydrate depuis les IDs partagés par Inertia.
         * Idempotent : on resynchronise à chaque navigation.
         */
        hydrate() {
            const page = usePage();
            this.productIds = [...(page.props?.auth?.favorite_product_ids ?? [])];
            this.serviceIds = [...(page.props?.auth?.favorite_service_ids ?? [])];
            this.hydrated = true;
        },

        isAuthenticated() {
            return !!usePage().props?.auth?.user;
        },

        listFor(type) {
            return type === 'service' ? this.serviceIds : this.productIds;
        },

        hasFor(type, id) {
            return this.listFor(type).includes(id);
        },

        /**
         * Bascule un favori (produit ou service). Optimiste + persistance serveur.
         * Retourne le nouvel état (true = favori).
         */
        toggle(type, id) {
            const isService = type === 'service';
            const list = isService ? 'serviceIds' : 'productIds';
            const wasFavorite = this[list].includes(id);

            if (wasFavorite) {
                this[list] = this[list].filter((x) => x !== id);
            } else {
                this[list].push(id);
            }

            router.post(
                route('favorites.toggle'),
                { type, id },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onError: () => {
                        if (wasFavorite) {
                            this[list].push(id);
                        } else {
                            this[list] = this[list].filter((x) => x !== id);
                        }
                    },
                },
            );

            return !wasFavorite;
        },

        // Rétrocompat : ancien appel ciblant les produits
        toggleProduct(productId) {
            return this.toggle('product', productId);
        },
    },
});
