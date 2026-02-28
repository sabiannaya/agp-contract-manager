<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Eye, MoreHorizontal, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ActionConfirmationDialog from '@/components/ActionConfirmationDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable, type DataTableColumn } from '@/components/ui/data-table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { StatusBadge } from '@/components/ui/status-badge';
import { useActionConfirmation } from '@/composables/useActionConfirmation';
import { useDateFormat } from '@/composables/useDateFormat';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createRoute, destroy as destroyRoute, edit as editRoute, index, show } from '@/routes/contracts';
import type { BreadcrumbItem } from '@/types';
import type {
    Contract,
    ContractFilters,
    PaginatedData,
} from '@/types/models';

const props = defineProps<{
    contracts: PaginatedData<Contract>;
    filters: ContractFilters;
}>();

const { t } = useI18n();
const { formatDateOnly } = useDateFormat();
const { canCreate, canUpdate, canDelete } = usePermission();
const deleteConfirmation = useActionConfirmation();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('nav.contracts'), href: index().url },
];

// Filter state
const searchInput = ref(props.filters.search ?? '');

const debouncedSearch = useDebounceFn((value: string) => {
    router.get(
        index().url,
        { ...props.filters, search: value || undefined, page: 1 },
        { preserveState: true, preserveScroll: true },
    );
}, 300);

watch(searchInput, (value) => debouncedSearch(value));

function handleSort(field: string, direction: 'asc' | 'desc') {
    router.get(
        index().url,
        { ...props.filters, sort: field, direction },
        { preserveState: true, preserveScroll: true },
    );
}

function handleNavigate(url: string) {
    router.get(url, {}, { preserveState: true, preserveScroll: true });
}

// Table columns
const columns: DataTableColumn<Contract>[] = [
    { key: 'number', label: t('contracts.number'), sortable: true },
    { key: 'date', label: t('contracts.date'), sortable: true },
    { key: 'vendor.name', label: t('contracts.vendor') },
    { key: 'cooperation_type', label: t('contracts.cooperation_type'), sortable: true },
    { key: 'amount', label: t('contracts.amount'), sortable: true, class: 'text-right' },
    { key: 'is_active', label: t('common.status'), sortable: true },
    { key: 'actions', label: '', class: 'w-[70px]' },
];

function formatCurrency(amount: number | undefined | null): string {
    if (amount == null) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

const editingContract = ref<Contract | null>(null);

function viewContract(contract: Contract) {
    router.get(show(contract.id).url);
}

function goCreatePage() {
    router.get(createRoute().url);
}

function goEditPage(contract: Contract) {
    router.get(editRoute(contract.id).url);
}

function openDeleteModal(contract: Contract) {
    editingContract.value = contract;
    deleteConfirmation.requestConfirmation(
        {
            title: t('common.confirm_delete'),
            description: t('contracts.delete_confirmation', { number: contract.number }),
            confirmText: t('common.delete'),
            destructive: true,
            details: [
                { label: t('contracts.number'), value: contract.number },
                { label: t('contracts.amount'), value: formatCurrency(contract.amount) },
            ],
        },
        confirmDelete,
    );
}

function confirmDelete() {
    if (!editingContract.value) return;

    router.delete(destroyRoute(editingContract.value.id).url, {
        onSuccess: () => {
            editingContract.value = null;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('nav.contracts')" />

        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <Heading :title="t('nav.contracts')" :description="t('contracts.description')" />

                <Button v-if="canCreate('contracts')" @click="goCreatePage">
                    <Plus class="mr-2 size-4" />
                    {{ t('contracts.create') }}
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-4">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="searchInput"
                        :placeholder="t('common.search')"
                        class="pl-9"
                    />
                </div>
            </div>

            <!-- Data Table -->
            <div class="overflow-auto">
                <DataTable
                    :columns="columns"
                    :data="(contracts.data as unknown as Record<string, unknown>[])"
                    :sort-field="typeof props.filters.sort === 'string' ? props.filters.sort : undefined"
                    :sort-direction="props.filters.direction"
                    :empty-text="t('common.no_data')"
                    hoverable
                    @sort="handleSort"
                >
                <template #cell-cooperation_type="{ value }">
                    <Badge :variant="value === 'progress' ? 'default' : 'secondary'">
                        {{ t(`contracts.types.${value}`) }}
                    </Badge>
                </template>

                <template #cell-date="{ value }">
                    {{ formatDateOnly(value as string) }}
                </template>

                <template #cell-amount="{ value }">
                    {{ formatCurrency(value as number) }}
                </template>

                <template #cell-is_active="{ value }">
                    <StatusBadge :status="value ? 'active' : 'inactive'" />
                </template>

                <template #cell-actions="{ row }">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="icon" class="size-8">
                                <MoreHorizontal class="size-4" />
                                <span class="sr-only">{{ t('common.actions') }}</span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @click="viewContract(row as unknown as Contract)">
                                <Eye class="mr-2 size-4" />
                                {{ t('common.view') }}
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="canUpdate('contracts')"
                                @click="goEditPage(row as unknown as Contract)"
                            >
                                <Pencil class="mr-2 size-4" />
                                {{ t('common.edit') }}
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="canDelete('contracts')"
                                class="text-destructive focus:text-destructive"
                                @click="openDeleteModal(row as unknown as Contract)"
                            >
                                <Trash2 class="mr-2 size-4" />
                                {{ t('common.delete') }}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>
                </DataTable>
            </div>

            <!-- Pagination -->
            <div class="shrink-0 border-t bg-background pt-4">
                <Pagination
                    :links="contracts.links"
                    :from="contracts.from"
                    :to="contracts.to"
                    :total="contracts.total"
                    @navigate="handleNavigate"
                />
            </div>
        </div>

        <ActionConfirmationDialog
            v-model:open="deleteConfirmation.open.value"
            :title="deleteConfirmation.title.value"
            :description="deleteConfirmation.description.value"
            :confirm-text="deleteConfirmation.confirmText.value"
            :cancel-text="deleteConfirmation.cancelText.value"
            :destructive="deleteConfirmation.destructive.value"
            :details="deleteConfirmation.details.value"
            @confirm="deleteConfirmation.confirm"
            @cancel="deleteConfirmation.cancel"
        />
    </AppLayout>
</template>
