import { useCartStore } from '@/stores/cart';
import { computed } from 'vue';

export function useCart() {
    const cartStore = useCartStore();

    const addToCart = async (product, quantity = 1) => {
        try {
            cartStore.addItem(product, quantity);
            return { ok: true };
        } catch (error) {
            return { ok: false, error };
        }
    };

    return {
        addToCart,
        cartItemsCount: computed(() => cartStore.itemsCount),
    };
}

