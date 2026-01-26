<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Eye, MoreHorizontal, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { DataTable, type DataTableColumn } from '@/components/ui/data-table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { StatusBadge } from '@/components/ui/status-badge';
import { useDateFormat } from '@/composables/useDateFormat';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createRoute, destroy as destroyRoute, edit as editRoute, index, show } from '@/routes/tickets';
import type { BreadcrumbItem } from '@/types';
import type {
    PaginatedData,
    Ticket,
    TicketFilters,
} from '@/types/models';

const props = defineProps<{
    tickets: PaginatedData<Ticket>;
    filters: TicketFilters;
}>();

const { t } = useI18n();
const { formatDateOnly } = useDateFormat();
const { canCreate, canUpdate, canDelete } = usePermission();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('nav.tickets'), href: index().url },
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
const columns: DataTableColumn<Ticket>[] = [
    { key: 'number', label: t('tickets.number'), sortable: true },
    { key: 'date', label: t('tickets.date'), sortable: true },
    { key: 'vendor.name', label: t('tickets.vendor') },
    { key: 'contract.number', label: t('tickets.contract') },
    { key: 'documents_count', label: t('tickets.documents') },
    { key: 'status', label: t('common.status'), sortable: true },
    { key: 'actions', label: '', class: 'w-[70px]' },
];

// Delete modal state
const isDeleteModalOpen = ref(false);
const editingTicket = ref<Ticket | null>(null);

function openCreatePage() {
    router.get(createRoute().url);
}

function viewTicket(ticket: Ticket) {
    router.get(show(ticket.id).url);
}

function openEditPage(ticket: Ticket) {
    router.get(editRoute(ticket.id).url);
}

function openDeleteModal(ticket: Ticket) {
    editingTicket.value = ticket;
    isDeleteModalOpen.value = true;
}

function confirmDelete() {
    if (!editingTicket.value) return;

    router.delete(destroyRoute(editingTicket.value.id).url, {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            editingTicket.value = null;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('nav.tickets')" />

        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <Heading :title="t('nav.tickets')" :description="t('tickets.description')" />

                <Button v-if="canCreate('tickets')" @click="openCreatePage">
                    <Plus class="mr-2 size-4" />
                    {{ t('tickets.create') }}
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
                    :data="(tickets.data as unknown as Record<string, unknown>[])"
                    :sort-field="typeof props.filters.sort === 'string' ? props.filters.sort : undefined"
                    :sort-direction="props.filters.direction"
                    :empty-text="t('common.no_data')"
                    hoverable
                    @sort="handleSort"
                >
                <template #cell-documents_count="{ row }">
                    {{ (row as unknown as Ticket).documents_count }}/5
                </template>

                <template #cell-date="{ value }">
                    {{ formatDateOnly(value as string) }}
                </template>

                <template #cell-status="{ value }">
                    <StatusBadge :status="value as string" />
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
                            <DropdownMenuItem
                                @click="viewTicket(row as unknown as Ticket)"
                            >
                                <Eye class="mr-2 size-4" />
                                {{ t('common.view') }}
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="canUpdate('tickets')"
                                @click="openEditPage(row as unknown as Ticket)"
                            >
                                <Pencil class="mr-2 size-4" />
                                {{ t('common.edit') }}
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="canDelete('tickets')"
                                class="text-destructive focus:text-destructive"
                                @click="openDeleteModal(row as unknown as Ticket)"
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
                    :links="tickets.links"
                    :from="tickets.from"
                    :to="tickets.to"
                    :total="tickets.total"
                    @navigate="handleNavigate"
                />
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Dialog v-model:open="isDeleteModalOpen">
            <DialogContent class="sm:max-w-100">
                <DialogHeader>
                    <DialogTitle>{{ t('common.confirm_delete') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('tickets.delete_confirmation', { number: editingTicket?.number }) }}
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="isDeleteModalOpen = false"
                    >
                        {{ t('common.cancel') }}
                    </Button>
                    <Button variant="destructive" @click="confirmDelete">
                        {{ t('common.delete') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
