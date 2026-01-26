<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Eye, MoreHorizontal, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy as destroyRoute, index, show, store, update } from '@/routes/vendors';
import type { BreadcrumbItem } from '@/types';
import type { PaginatedData, Vendor, VendorFilters, VendorForm } from '@/types/models';

const props = defineProps<{
    vendors: PaginatedData<Vendor>;
    filters: VendorFilters;
}>();

const { t } = useI18n();
const { canCreate, canUpdate, canDelete } = usePermission();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('nav.vendors'), href: index().url },
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
const columns: DataTableColumn<Vendor>[] = [
    { key: 'code', label: t('vendors.code'), sortable: true },
    { key: 'name', label: t('vendors.name'), sortable: true },
    { key: 'address', label: t('vendors.address') },
    { key: 'contact_person', label: t('vendors.contact_person') },
    { key: 'tax_id', label: t('vendors.tax_id') },
    { key: 'is_active', label: t('common.status'), sortable: true },
    { key: 'actions', label: '', class: 'w-[70px]' },
];

// Modal state
const isFormModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const editingVendor = ref<Vendor | null>(null);

const form = useForm<VendorForm>({
    code: '',
    name: '',
    address: '',
    join_date: new Date().toISOString().split('T')[0],
    contact_person: '',
    tax_id: '',
    is_active: true,
});

const modalTitle = computed(() =>
    editingVendor.value ? t('vendors.edit') : t('vendors.create'),
);

function openCreateModal() {
    editingVendor.value = null;
    form.reset();
    form.clearErrors();
    isFormModalOpen.value = true;
}

function viewVendor(vendor: Vendor) {
    router.get(show(vendor.id).url);
}

function openEditModal(vendor: Vendor) {
    editingVendor.value = vendor;
    form.code = vendor.code;
    form.name = vendor.name;
    form.address = vendor.address;
    form.join_date = vendor.join_date;
    form.contact_person = vendor.contact_person;
    form.tax_id = vendor.tax_id;
    form.is_active = vendor.is_active;
    form.clearErrors();
    isFormModalOpen.value = true;
}

function openDeleteModal(vendor: Vendor) {
    editingVendor.value = vendor;
    isDeleteModalOpen.value = true;
}

function submitForm() {
    if (editingVendor.value) {
        form.put(update(editingVendor.value.id).url, {
            onSuccess: () => {
                isFormModalOpen.value = false;
            },
        });
    } else {
        form.post(store().url, {
            onSuccess: () => {
                isFormModalOpen.value = false;
            },
        });
    }
}

function confirmDelete() {
    if (!editingVendor.value) return;

    router.delete(destroyRoute(editingVendor.value.id).url, {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            editingVendor.value = null;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('nav.vendors')" />

        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <Heading :title="t('nav.vendors')" :description="t('vendors.description')" />

                <Button v-if="canCreate('vendors')" @click="openCreateModal">
                    <Plus class="mr-2 size-4" />
                    {{ t('vendors.create') }}
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
                    :data="(vendors.data as unknown as Record<string, unknown>[])"
                    :sort-field="typeof props.filters.sort === 'string' ? props.filters.sort : undefined"
                    :sort-direction="props.filters.direction"
                    :empty-text="t('common.no_data')"
                    hoverable
                    @sort="handleSort"
                >
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
                            <DropdownMenuItem @click="viewVendor(row as unknown as Vendor)">
                                <Eye class="mr-2 size-4" />
                                {{ t('common.view') }}
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="canUpdate('vendors')"
                                @click="openEditModal(row as unknown as Vendor)"
                            >
                                <Pencil class="mr-2 size-4" />
                                {{ t('common.edit') }}
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="canDelete('vendors')"
                                class="text-destructive focus:text-destructive"
                                @click="openDeleteModal(row as unknown as Vendor)"
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
                    :links="vendors.links"
                    :from="vendors.from"
                    :to="vendors.to"
                    :total="vendors.total"
                    @navigate="handleNavigate"
                />
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Dialog v-model:open="isFormModalOpen">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>{{ modalTitle }}</DialogTitle>
                    <DialogDescription>
                        {{ editingVendor ? t('vendors.edit_description') : t('vendors.create_description') }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid gap-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('vendors.code') }}</label>
                                <Input v-model="form.code" :disabled="form.processing" />
                                <p v-if="form.errors.code" class="text-sm text-destructive">
                                    {{ form.errors.code }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('vendors.join_date') }}</label>
                                <Input
                                    v-model="form.join_date"
                                    type="date"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.join_date" class="text-sm text-destructive">
                                    {{ form.errors.join_date }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('vendors.name') }}</label>
                            <Input v-model="form.name" :disabled="form.processing" />
                            <p v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('vendors.address') }}</label>
                            <Input v-model="form.address" :disabled="form.processing" />
                            <p v-if="form.errors.address" class="text-sm text-destructive">
                                {{ form.errors.address }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('vendors.contact_person') }}</label>
                                <Input v-model="form.contact_person" :disabled="form.processing" />
                                <p v-if="form.errors.contact_person" class="text-sm text-destructive">
                                    {{ form.errors.contact_person }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('vendors.tax_id') }}</label>
                                <Input v-model="form.tax_id" :disabled="form.processing" />
                                <p v-if="form.errors.tax_id" class="text-sm text-destructive">
                                    {{ form.errors.tax_id }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="isFormModalOpen = false"
                            :disabled="form.processing"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? t('common.saving') : t('common.save') }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Modal -->
        <Dialog v-model:open="isDeleteModalOpen">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>{{ t('common.confirm_delete') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('vendors.delete_confirmation', { name: editingVendor?.name }) }}
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
