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
        // Identifie une ligne de panier. Pour les produits (historique) la clé
        // reste l'id seul ; les services sont distingués par leur type pour
        // éviter une collision d'id avec un produit du même numéro.
        matchesItem(item, id, type) {
            if (type === 'service') {
                return item.type === 'service' && item.id === id;
            }
            // produit : compatible avec les anciennes lignes sans champ type
            return (item.type ?? 'product') === 'product' && item.id === id;
        },
        addItem(product, quantity = 1) {
            const type = product.type === 'service' ? 'service' : 'product';
            const existing = this.items.find((item) => this.matchesItem(item, product.id, type));

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
                type,
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
        removeItem(productId, type = 'product') {
            this.items = this.items.filter((item) => !this.matchesItem(item, productId, type));
            this.persist();
        },
        updateQuantity(productId, quantity, type = 'product') {
            const existing = this.items.find((item) => this.matchesItem(item, productId, type));
            if (!existing) return;

            if (quantity <= 0) {
                this.removeItem(productId, type);
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

