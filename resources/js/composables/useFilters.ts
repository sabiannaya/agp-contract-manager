import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';

export interface UseFiltersOptions<T extends Record<string, unknown>> {
    route: string;
    initialFilters: T;
    debounceMs?: number;
}

export function useFilters<T extends Record<string, unknown>>({
    route,
    initialFilters,
    debounceMs = 300,
}: UseFiltersOptions<T>) {
    const filters = ref<T>({ ...initialFilters });
    const isFiltering = ref(false);

    // Apply filters with debounce
    const applyFilters = useDebounceFn(() => {
        isFiltering.value = true;

        // Clean up empty values
        const cleanFilters: Record<string, unknown> = {};
        Object.entries(filters.value as Record<string, unknown>).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) {
                cleanFilters[key] = value;
            }
        });

        router.get(
            route,
            cleanFilters as Record<string, string | number>,
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => {
                    isFiltering.value = false;
                },
            },
        );
    }, debounceMs);

    // Reset all filters
    function resetFilters() {
        filters.value = { ...initialFilters };
        applyFilters();
    }

    // Update a single filter
    function setFilter<K extends keyof T>(key: K, value: T[K]) {
        filters.value[key] = value;
        applyFilters();
    }

    // Sorting helper
    function handleSort(field: string, direction: 'asc' | 'desc') {
        (filters.value as Record<string, unknown>).sort = field;
        (filters.value as Record<string, unknown>).direction = direction;
        applyFilters();
    }

    // Pagination helper
    function handlePaginate(url: string) {
        isFiltering.value = true;
        router.get(
            url,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => {
                    isFiltering.value = false;
                },
            },
        );
    }

    return {
        filters,
        isFiltering,
        applyFilters,
        resetFilters,
        setFilter,
        handleSort,
        handlePaginate,
    };
}
