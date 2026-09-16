import { defineStore } from 'pinia';

const STORAGE_KEY = 'maketushop-cart';

/**
 * Prix unitaire d'une ligne de panier.
 *
 * Les pages ne sérialisent pas toutes le produit de la même façon : les
 * contrôleurs Produit/Service exposent « current_price », l'accueil « price ».
 * Ne lire qu'un seul de ces noms enregistrait un prix indéfini, affiché 0.
 */
const resolvePrice = (source) => {
    const value = Number(source?.current_price ?? source?.price);
    return Number.isFinite(value) && value >= 0 ? value : 0;
};

/** Une ligne dont le prix est inexploitable doit être réalignée sur le serveur. */
const hasUsablePrice = (item) => Number.isFinite(Number(item?.price)) && Number(item.price) > 0;

const normalizeItem = (item) => ({
    ...item,
    type: item.type ?? 'product',
    price: resolvePrice(item),
    quantity: Math.max(1, Number(item?.quantity) || 1),
});

const readStoredItems = () => {
    if (typeof window === 'undefined') return [];

    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) return [];

        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed.map(normalizeItem) : [];
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
        // Lignes dont le prix n'a pas pu être déterminé : le total affiché serait faux.
        hasUnpricedItems: (state) => state.items.some((item) => !hasUsablePrice(item)),
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
                price: resolvePrice(product),
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

        /**
         * Réaligne sur le serveur les lignes incomplètes : prix inexploitable ou
         * boutique manquante. Répare notamment les paniers déjà enregistrés en
         * localStorage avec un prix indéfini.
         *
         * Les services sont exclus : l'endpoint ne connaît que les produits, et
         * un service sur devis n'a légitimement pas de prix.
         */
        async hydrateFromServer() {
            const stale = this.items.filter(
                (item) =>
                    (item.type ?? 'product') === 'product' &&
                    (!hasUsablePrice(item) || !item.shop?.id || !item.shop?.owner_phone),
            );

            if (!stale.length || typeof window === 'undefined' || !window.axios) return;

            const ids = [...new Set(stale.map((item) => item.id))];

            try {
                const url = typeof route === 'function' ? route('cart.metadata') : '/cart/metadata';
                const { data } = await window.axios.get(url, { params: { ids } });
                const metadata = data?.metadata || {};

                this.items = this.items.map((item) => {
                    const meta = metadata[item.id];
                    if (!meta || (item.type ?? 'product') !== 'product') return item;

                    return {
                        ...item,
                        name: item.name || meta.name,
                        price: hasUsablePrice(item) ? Number(item.price) : resolvePrice(meta),
                        shop: {
                            id: meta.shop?.id ?? item.shop?.id ?? null,
                            name: meta.shop?.name ?? item.shop?.name ?? 'Boutique inconnue',
                            logo: meta.shop?.logo ?? item.shop?.logo ?? null,
                            owner_phone: meta.shop?.owner_phone ?? item.shop?.owner_phone ?? null,
                        },
                    };
                });

                this.persist();
            } catch {
                // Hors ligne ou endpoint indisponible : on conserve l'état courant.
            }
        },
    },
});

