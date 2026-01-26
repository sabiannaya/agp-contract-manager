<script setup lang="ts" generic="T extends Record<string, unknown>">
import type { HTMLAttributes } from 'vue';
import { ChevronUp, ChevronDown, ChevronsUpDown } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import { Spinner } from '@/components/ui/spinner';

export interface DataTableColumn<T> {
    key: string;
    label: string;
    sortable?: boolean;
    class?: string;
    headerClass?: string;
}

const props = defineProps<{
    columns: DataTableColumn<T>[];
    data: T[];
    sortField?: string;
    sortDirection?: 'asc' | 'desc';
    loading?: boolean;
    emptyText?: string;
    class?: HTMLAttributes['class'];
    striped?: boolean;
    hoverable?: boolean;
    pageSize?: number;
    innerPagination?: boolean;
}>();

const emits = defineEmits<{
    (e: 'sort', field: string, direction: 'asc' | 'desc'): void;
}>();

function handleSort(column: DataTableColumn<T>) {
    if (!column.sortable) return;

    let newDirection: 'asc' | 'desc' = 'asc';
    if (props.sortField === column.key) {
        newDirection = props.sortDirection === 'asc' ? 'desc' : 'asc';
    }

    emits('sort', column.key, newDirection);
}

function getSortIcon(column: DataTableColumn<T>) {
    if (!column.sortable) return null;
    if (props.sortField !== column.key) return ChevronsUpDown;
    return props.sortDirection === 'asc' ? ChevronUp : ChevronDown;
}

function getCellValue(row: T, key: string): unknown {
    // Support nested keys like 'vendor.name'
    return key.split('.').reduce<unknown>((obj, k) => (obj as Record<string, unknown>)?.[k], row);
}

// Client-side pagination state
import { computed, ref, watch } from 'vue';
const currentPage = ref(1);
const effectivePageSize = computed(() => props.pageSize ?? 7);
const useInnerPagination = computed(() => props.innerPagination ?? true);
const totalItems = computed(() => props.data.length);
const totalPages = computed(() => Math.max(1, Math.ceil(totalItems.value / effectivePageSize.value)));
const pagedData = computed(() => {
    if (!useInnerPagination.value) return props.data;
    const start = (currentPage.value - 1) * effectivePageSize.value;
    return props.data.slice(start, start + effectivePageSize.value);
});

watch(() => props.data, () => {
    // Reset to first page when data changes
    currentPage.value = 1;
});

function prevPage() {
    if (currentPage.value > 1) currentPage.value -= 1;
}

function nextPage() {
    if (currentPage.value < totalPages.value) currentPage.value += 1;
}
</script>

<template>
    <div :class="cn('w-full overflow-auto rounded-md border', props.class)">
        <table class="w-full caption-bottom text-sm">
            <thead class="[&_tr]:border-b bg-muted/50">
                <tr class="border-b transition-colors">
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        :class="
                            cn(
                                'h-10 px-4 text-left align-middle font-medium text-muted-foreground',
                                column.sortable && 'cursor-pointer select-none hover:text-foreground',
                                column.headerClass,
                            )
                        "
                        @click="handleSort(column)"
                    >
                        <div class="flex items-center gap-1">
                            <span>{{ column.label }}</span>
                            <component
                                :is="getSortIcon(column)"
                                v-if="getSortIcon(column)"
                                class="size-4"
                            />
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
                <!-- Loading state -->
                <tr v-if="loading">
                    <td :colspan="columns.length" class="h-24 text-center">
                        <div class="flex items-center justify-center gap-2 text-muted-foreground">
                            <Spinner class="size-5" />
                            <span>Loading...</span>
                        </div>
                    </td>
                </tr>

                <!-- Empty state -->
                <tr v-else-if="!data.length">
                    <td :colspan="columns.length" class="h-24 text-center text-muted-foreground">
                        {{ emptyText ?? 'No data available.' }}
                    </td>
                </tr>

                <!-- Data rows -->
                <tr
                    v-else
                    v-for="(row, index) in pagedData"
                    :key="index"
                    :class="
                        cn(
                            'border-b transition-colors',
                            hoverable && 'hover:bg-muted/50',
                            striped && index % 2 === 1 && 'bg-muted/20',
                        )
                    "
                >
                    <td
                        v-for="column in columns"
                        :key="column.key"
                        :class="cn('p-4 align-middle', column.class)"
                    >
                        <slot :name="`cell-${column.key}`" :row="row" :value="getCellValue(row, column.key)">
                            {{ getCellValue(row, column.key) }}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
        <!-- Inner pagination controls -->
        <div v-if="useInnerPagination && totalItems > effectivePageSize" class="flex items-center justify-between p-3 text-sm text-muted-foreground">
            <p>
                Showing
                <span class="font-medium">{{ (currentPage - 1) * effectivePageSize + 1 }}</span>
                to
                <span class="font-medium">{{ Math.min(currentPage * effectivePageSize, totalItems) }}</span>
                of
                <span class="font-medium">{{ totalItems }}</span>
                results
            </p>
            <div class="flex items-center gap-2">
                <button class="inline-flex items-center rounded-md border px-2 py-1" :disabled="currentPage === 1" @click="prevPage">Prev</button>
                <span>Page {{ currentPage }} of {{ totalPages }}</span>
                <button class="inline-flex items-center rounded-md border px-2 py-1" :disabled="currentPage === totalPages" @click="nextPage">Next</button>
            </div>
        </div>
    </div>
</template>
