<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Eye, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable, type DataTableColumn } from '@/components/ui/data-table';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { useDateFormat } from '@/composables/useDateFormat';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Ticket, PaginatedData } from '@/types/models';

interface PaymentTrackerFilters {
    search?: string;
    approval_status?: string;
    contract_id?: number;
    vendor_id?: number;
    date_from?: string;
    date_to?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
}

const props = defineProps<{
    tickets: PaginatedData<Ticket>;
    filters: PaymentTrackerFilters;
}>();

const { t } = useI18n();
const { formatDateOnly } = useDateFormat();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('nav.payment_tracker'), href: '/payment-tracker' },
];

// Filter state
const searchInput = ref(props.filters.search ?? '');

const debouncedSearch = useDebounceFn((value: string) => {
    router.get(
        '/payment-tracker',
        { ...props.filters, search: value || undefined, page: 1 },
        { preserveState: true, preserveScroll: true },
    );
}, 300);

watch(searchInput, (value) => debouncedSearch(value));

function handleSort(field: string, direction: 'asc' | 'desc') {
    router.get(
        '/payment-tracker',
        { ...props.filters, sort: field, direction },
        { preserveState: true, preserveScroll: true },
    );
}

function handleNavigate(url: string) {
    router.get(url, {}, { preserveState: true, preserveScroll: true });
}

function formatCurrency(amount: number | null | undefined): string {
    if (amount == null) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

function approvalStatusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'paid': return 'default';
        case 'approved': return 'default';
        case 'pending': return 'secondary';
        case 'rejected': return 'destructive';
        default: return 'outline';
    }
}

function viewTicket(ticket: Ticket) {
    router.get(`/payment-tracker/${ticket.id}`);
}

const columns: DataTableColumn<Ticket>[] = [
    { key: 'number', label: t('tickets.number'), sortable: true },
    { key: 'date', label: t('tickets.date'), sortable: true },
    { key: 'contract.number', label: t('tickets.contract') },
    { key: 'vendor.name', label: t('tickets.vendor') },
    { key: 'amount', label: t('payment_tracker.amount'), sortable: true, class: 'text-right' },
    { key: 'approval_status', label: t('payment_tracker.approval_status'), sortable: true },
    { key: 'actions', label: '', class: 'w-[70px]' },
];

function filterByStatus(status: string | undefined) {
    router.get(
        '/payment-tracker',
        { ...props.filters, approval_status: status || undefined, page: 1 },
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('nav.payment_tracker')" />

        <div class="flex flex-col gap-6 p-6">
            <Heading :title="t('nav.payment_tracker')" :description="t('payment_tracker.description')" />

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-4">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="searchInput"
                        :placeholder="t('common.search')"
                        class="pl-9"
                    />
                </div>

                <div class="flex gap-2">
                    <Button
                        size="sm"
                        :variant="!filters.approval_status ? 'default' : 'outline'"
                        @click="filterByStatus(undefined)"
                    >
                        {{ t('common.all') }}
                    </Button>
                    <Button
                        v-for="status in ['draft', 'pending', 'approved', 'rejected', 'paid']"
                        :key="status"
                        size="sm"
                        :variant="filters.approval_status === status ? 'default' : 'outline'"
                        @click="filterByStatus(status)"
                    >
                        {{ t(`payment_tracker.statuses.${status}`) }}
                    </Button>
                </div>
            </div>

            <!-- Data Table -->
            <div class="overflow-auto">
                <DataTable
                    :columns="columns"
                    :data="(tickets.data as unknown as Record<string, unknown>[])"
                    :sort-field="typeof props.filters.sort === 'string' ? props.filters.sort : undefined"
                    :sort-direction="props.filters.direction"
                    :empty-text="t('common.no_data')"
                    hoverable
                    @sort="handleSort"
                >
                    <template #cell-date="{ value }">
                        {{ formatDateOnly(value as string) }}
                    </template>

                    <template #cell-amount="{ value }">
                        {{ formatCurrency(value as number) }}
                    </template>

                    <template #cell-approval_status="{ value }">
                        <Badge :variant="approvalStatusVariant(value as string)">
                            {{ t(`payment_tracker.statuses.${value}`) }}
                        </Badge>
                    </template>

                    <template #cell-actions="{ row }">
                        <Button variant="ghost" size="icon" class="size-8" @click="viewTicket(row as unknown as Ticket)">
                            <Eye class="size-4" />
                            <span class="sr-only">{{ t('common.view') }}</span>
                        </Button>
                    </template>
                </DataTable>
            </div>

            <!-- Pagination -->
            <div class="shrink-0 border-t bg-background pt-4">
                <Pagination
                    :links="tickets.links"
                    :from="tickets.from"
                    :to="tickets.to"
                    :total="tickets.total"
                    @navigate="handleNavigate"
                />
            </div>
        </div>
    </AppLayout>
</template>
