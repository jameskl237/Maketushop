import { computed } from 'vue';

export function useFilters(filters) {
    const hasActiveFilters = computed(() => {
        return Boolean(
            filters.search ||
                filters.in_stock ||
                filters.price_min ||
                filters.price_max ||
                (filters.categories?.length ?? 0) > 0 ||
                (filters.locations?.length ?? 0) > 0 ||
                (filters.sort && filters.sort !== 'newest'),
        );
    });

    const toQueryParams = () => ({
        search: filters.search || undefined,
        categories: filters.categories?.length ? filters.categories : undefined,
        locations: filters.locations?.length ? filters.locations : undefined,
        price_min: filters.price_min || undefined,
        price_max: filters.price_max || undefined,
        in_stock: filters.in_stock ? 1 : undefined,
        sort: filters.sort || 'newest',
    });

    return { hasActiveFilters, toQueryParams };
}

