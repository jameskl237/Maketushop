import { defineStore } from 'pinia';

const STORAGE_KEY = 'maketushop-cart';

const readStoredItems = () => {
    if (typeof window === 'undefined') return [];

    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) return [];

        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
};

export const useCartStore = defineStore('cart', {
    state: () => ({
        items: readStoredItems(),
    }),
    getters: {
        itemsCount: (state) => state.items.reduce((total, item) => total + item.quantity, 0),
        totalAmount: (state) => state.items.reduce((total, item) => total + (Number(item.price) || 0) * item.quantity, 0),
    },
    actions: {
        persist() {
            if (typeof window === 'undefined') return;
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(this.items));
        },
        addItem(product, quantity = 1) {
            const existing = this.items.find((item) => item.id === product.id);

            if (existing) {
                existing.quantity += quantity;
                // Keep legacy cart rows compatible by refreshing missing shop metadata.
                existing.shop = {
                    id: product.shop?.id ?? existing.shop?.id ?? null,
                    name: product.shop?.name ?? existing.shop?.name ?? 'Boutique inconnue',
                    logo: product.shop?.logo ?? existing.shop?.logo ?? null,
                    owner_phone: product.shop?.owner_phone ?? existing.shop?.owner_phone ?? null,
                };
                this.persist();
                return;
            }

            this.items.push({
                id: product.id,
                name: product.name,
                price: product.current_price,
                image: product.main_image,
                quantity,
                shop: {
                    id: product.shop?.id ?? null,
                    name: product.shop?.name ?? 'Boutique inconnue',
                    logo: product.shop?.logo ?? null,
                    owner_phone: product.shop?.owner_phone ?? null,
                },
            });
            this.persist();
        },
        removeItem(productId) {
            this.items = this.items.filter((item) => item.id !== productId);
            this.persist();
        },
        updateQuantity(productId, quantity) {
            const existing = this.items.find((item) => item.id === productId);
            if (!existing) return;

            if (quantity <= 0) {
                this.removeItem(productId);
                return;
            }

            existing.quantity = quantity;
            this.persist();
        },
        clear() {
            this.items = [];
            this.persist();
        },
    },
});

