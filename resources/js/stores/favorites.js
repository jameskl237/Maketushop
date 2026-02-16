import { defineStore } from 'pinia';

export const useFavoritesStore = defineStore('favorites', {
    state: () => ({
        productIds: [],
    }),
    getters: {
        has: (state) => (productId) => state.productIds.includes(productId),
    },
    actions: {
        toggle(productId) {
            if (this.productIds.includes(productId)) {
                this.productIds = this.productIds.filter((id) => id !== productId);
                return false;
            }

            this.productIds.push(productId);
            return true;
        },
    },
});

