import { ref } from 'vue';

export function useSearch(callback, delay = 500) {
    const isSearching = ref(false);
    let timeoutId = null;

    const search = () => {
        isSearching.value = true;

        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        timeoutId = setTimeout(() => {
            callback();
            isSearching.value = false;
        }, delay);
    };

    return { search, isSearching };
}

