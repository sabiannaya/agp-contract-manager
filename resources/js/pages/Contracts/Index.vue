<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Eye, MoreHorizontal, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ActionConfirmationDialog from '@/components/ActionConfirmationDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
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
import { CurrencyInput } from '@/components/ui/currency-input';
import { Label } from '@/components/ui/label';
import { Pagination } from '@/components/ui/pagination';
import { Select } from '@/components/ui/select';
import { StatusBadge } from '@/components/ui/status-badge';
import { useActionConfirmation } from '@/composables/useActionConfirmation';
import { useDateFormat } from '@/composables/useDateFormat';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createRoute, destroy as destroyRoute, edit as editRoute, index, show, store, update } from '@/routes/contracts';
import { list as vendorList } from '@/routes/vendors';
import type { BreadcrumbItem } from '@/types';
import type {
    Contract,
    ContractFilters,
    ContractForm,
    PaginatedData,
    VendorOption,
} from '@/types/models';

interface EligibleUser {
    id: number;
    name: string;
    email: string;
}

const props = defineProps<{
    contracts: PaginatedData<Contract>;
    filters: ContractFilters;
    eligibleMasters?: EligibleUser[];
}>();

const { t } = useI18n();
const { formatDateOnly } = useDateFormat();
const { canCreate, canUpdate, canDelete } = usePermission();
const saveConfirmation = useActionConfirmation();
const deleteConfirmation = useActionConfirmation();
const approverConfirmation = useActionConfirmation();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('nav.contracts'), href: index().url },
];

// Vendors for dropdown
const vendors = ref<VendorOption[]>([]);
const loadingVendors = ref(false);

async function loadVendors() {
    loadingVendors.value = true;
    try {
        const response = await fetch(vendorList().url);
        vendors.value = await response.json();
    } finally {
        loadingVendors.value = false;
    }
}

onMounted(loadVendors);

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

// Select options
const vendorOptions = computed(() =>
    vendors.value.map((v) => ({ value: v.id, label: `${v.code} - ${v.name}` })),
);

const cooperationTypeOptions = computed(() => [
    { value: 'routine', label: t('contracts.types.routine') },
    { value: 'progress', label: t('contracts.types.progress') },
]);

const masterOptions = computed(() =>
    (props.eligibleMasters ?? []).map((u) => ({ value: u.id, label: `${u.name} (${u.email})` })),
);

const approverOptions = computed(() => masterOptions.value);

type ApproverRow = {
    user_id: number | null;
    sequence_no: number;
    remarks: string;
};

// Modal state
const isFormModalOpen = ref(false);
const editingContract = ref<Contract | null>(null);

const form = useForm<ContractForm & { assigned_master_user_id: number | null }>({
    number: '',
    date: new Date().toISOString().split('T')[0],
    vendor_id: null,
    amount: 0,
    cooperation_type: 'routine',
    term_count: null,
    term_percentages: [],
    is_active: true,
    assigned_master_user_id: null,
});

const approverForm = useForm<{ approvers: ApproverRow[] }>({
    approvers: [{ user_id: null, sequence_no: 1, remarks: '' }],
});

const modalTitle = computed(() =>
    editingContract.value ? t('contracts.edit') : t('contracts.create'),
);

// Term percentages handling
const termInputs = ref<number[]>([]);

watch(() => form.term_count, (count) => {
    if (count && count > 0) {
        const current = [...termInputs.value];
        if (count > current.length) {
            // Add new terms
            for (let i = current.length; i < count; i++) {
                current.push(0);
            }
        } else {
            // Trim excess terms
            current.splice(count);
        }
        termInputs.value = current;
        form.term_percentages = current;
    } else {
        termInputs.value = [];
        form.term_percentages = [];
    }
});

watch(termInputs, (inputs) => {
    form.term_percentages = [...inputs];
}, { deep: true });

const totalPercentage = computed(() =>
    termInputs.value.reduce((sum, val) => sum + (val || 0), 0),
);

function openCreateModal() {
    editingContract.value = null;
    form.reset();
    form.clearErrors();
    termInputs.value = [];
    form.assigned_master_user_id = null;
    approverForm.approvers = [{ user_id: null, sequence_no: 1, remarks: '' }];
    approverForm.clearErrors();
    isFormModalOpen.value = true;
}

function viewContract(contract: Contract) {
    router.get(show(contract.id).url);
}

function goCreatePage() {
    router.get(createRoute().url);
}

function goEditPage(contract: Contract) {
    router.get(editRoute(contract.id).url);
}

function openEditModal(contract: Contract) {
    editingContract.value = contract;
    form.number = contract.number;
    form.date = contract.date;
    form.vendor_id = contract.vendor_id;
    form.amount = contract.amount;
    form.cooperation_type = contract.cooperation_type;
    form.term_count = contract.term_count;
    form.term_percentages = contract.term_percentages ?? [];
    termInputs.value = contract.term_percentages ?? [];
    form.is_active = contract.is_active;
    form.assigned_master_user_id = (contract as any).assigned_master_user_id ?? null;
    form.clearErrors();
    hydrateApproverForm(contract);
    approverForm.clearErrors();
    isFormModalOpen.value = true;
}

function hydrateApproverForm(contract: Contract | null) {
    approverForm.approvers = ((contract as any)?.approvers ?? []).map((approver: any, index: number) => ({
        user_id: approver.user_id ?? null,
        sequence_no: approver.sequence_no ?? index + 1,
        remarks: approver.remarks ?? '',
    }));

    if (approverForm.approvers.length === 0) {
        approverForm.approvers = [{ user_id: null, sequence_no: 1, remarks: '' }];
    }
}

function normalizeApproverSequence() {
    approverForm.approvers = approverForm.approvers.map((row, index) => ({
        ...row,
        sequence_no: index + 1,
    }));
}

function addApproverRow() {
    approverForm.approvers.push({
        user_id: null,
        sequence_no: approverForm.approvers.length + 1,
        remarks: '',
    });
}

function removeApproverRow(index: number) {
    approverForm.approvers.splice(index, 1);
    normalizeApproverSequence();
}

function executeSaveApprovers() {
    if (!editingContract.value) return;

    normalizeApproverSequence();
    approverForm.post(`/contracts/${editingContract.value.id}/approvers`, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['contracts'] });
        },
    });
}

function submitApprovers() {
    if (!editingContract.value) return;

    approverConfirmation.requestConfirmation(
        {
            title: t('contracts.approvers'),
            description: 'Update contract approvers and their workflow reason.',
            confirmText: t('common.save'),
            details: [
                { label: t('contracts.number'), value: editingContract.value.number },
                { label: t('contracts.approvers'), value: String(approverForm.approvers.length) },
            ],
        },
        executeSaveApprovers,
    );
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

function executeSubmitForm() {
    if (editingContract.value) {
        form.put(update(editingContract.value.id).url, {
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

function submitForm() {
    saveConfirmation.requestConfirmation(
        {
            title: editingContract.value ? t('common.update') : t('common.create'),
            description: editingContract.value ? t('contracts.edit_description') : t('contracts.create_description'),
            confirmText: t('common.save'),
            details: [
                { label: t('contracts.number'), value: form.number || '-' },
                { label: t('contracts.vendor'), value: vendorOptions.value.find((v) => v.value === form.vendor_id)?.label ?? '-' },
                { label: t('contracts.amount'), value: formatCurrency(form.amount ?? 0) },
            ],
        },
        executeSubmitForm,
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

        <!-- Create/Edit Modal -->
        <Dialog v-model:open="isFormModalOpen">
            <DialogContent class="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>{{ modalTitle }}</DialogTitle>
                    <DialogDescription>
                        {{ editingContract ? t('contracts.edit_description') : t('contracts.create_description') }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid gap-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label>{{ t('contracts.number') }}</Label>
                                <Input v-model="form.number" :disabled="form.processing" />
                                <p v-if="form.errors.number" class="text-sm text-destructive">
                                    {{ form.errors.number }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label>{{ t('contracts.date') }}</Label>
                                <Input
                                    v-model="form.date"
                                    type="date"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.date" class="text-sm text-destructive">
                                    {{ form.errors.date }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label>{{ t('contracts.vendor') }}</Label>
                            <Select
                                v-model="form.vendor_id"
                                :options="vendorOptions"
                                :placeholder="t('contracts.select_vendor')"
                                :disabled="form.processing"
                                clearable
                            />
                            <p v-if="form.errors.vendor_id" class="text-sm text-destructive">
                                {{ form.errors.vendor_id }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label>{{ t('contracts.amount') }}</Label>
                                <CurrencyInput
                                    v-model="form.amount"
                                    :min="0"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.amount" class="text-sm text-destructive">
                                    {{ form.errors.amount }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label>{{ t('contracts.cooperation_type') }}</Label>
                                <Select
                                    v-model="form.cooperation_type"
                                    :options="cooperationTypeOptions"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.cooperation_type" class="text-sm text-destructive">
                                    {{ form.errors.cooperation_type }}
                                </p>
                            </div>
                        </div>

                        <!-- Assigned Contract Master -->
                        <div class="space-y-2">
                            <Label>{{ t('contracts.assigned_master') }}</Label>
                            <Select
                                v-model="form.assigned_master_user_id"
                                :options="masterOptions"
                                :placeholder="t('contracts.select_master')"
                                :disabled="form.processing"
                                clearable
                            />
                            <p class="text-xs text-muted-foreground">{{ t('contracts.master_hint') }}</p>
                            <p v-if="form.errors.assigned_master_user_id" class="text-sm text-destructive">
                                {{ form.errors.assigned_master_user_id }}
                            </p>
                        </div>

                        <div v-if="editingContract" class="space-y-3 rounded-lg border p-4">
                            <div class="flex items-center justify-between">
                                <Label class="font-medium">{{ t('contracts.approvers') }}</Label>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="addApproverRow"
                                    :disabled="approverForm.processing"
                                >
                                    <Plus class="mr-1 size-4" />
                                    {{ t('common.create') }}
                                </Button>
                            </div>

                            <div
                                v-for="(row, index) in approverForm.approvers"
                                :key="`approver-index-row-${index}`"
                                class="grid gap-3 rounded-md border p-3 lg:grid-cols-12"
                            >
                                <div class="lg:col-span-1">
                                    <Label>Seq</Label>
                                    <Input :model-value="row.sequence_no" disabled />
                                </div>
                                <div class="lg:col-span-4">
                                    <Label>{{ t('nav.users') }}</Label>
                                    <Select
                                        v-model="row.user_id"
                                        :options="approverOptions"
                                        :placeholder="t('common.select')"
                                        :disabled="approverForm.processing"
                                        clearable
                                    />
                                </div>
                                <div class="lg:col-span-6">
                                    <Label>Remarks</Label>
                                    <Input
                                        v-model="row.remarks"
                                        :placeholder="'Why this approver is required'"
                                        :disabled="approverForm.processing"
                                    />
                                </div>
                                <div class="flex items-end lg:col-span-1">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        :disabled="approverForm.processing || approverForm.approvers.length <= 1"
                                        @click="removeApproverRow(index)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </div>

                            <p v-if="approverForm.errors.approvers" class="text-sm text-destructive">
                                {{ approverForm.errors.approvers }}
                            </p>

                            <div class="flex justify-end">
                                <Button type="button" variant="secondary" :disabled="approverForm.processing" @click="submitApprovers">
                                    {{ approverForm.processing ? t('common.saving') : t('common.save') }} {{ t('contracts.approvers') }}
                                </Button>
                            </div>
                        </div>

                        <p v-else class="text-xs text-muted-foreground">
                            Approvers can be configured after contract is created.
                        </p>

                        <!-- Term fields for progress type -->
                        <div v-if="form.cooperation_type === 'progress'" class="space-y-4 border rounded-lg p-4">
                            <div class="space-y-2">
                                <Label>{{ t('contracts.term_count') }}</Label>
                                <Input
                                    :model-value="form.term_count ?? undefined"
                                    @update:model-value="form.term_count = $event ? Number($event) : null"
                                    type="number"
                                    min="1"
                                    max="12"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.term_count" class="text-sm text-destructive">
                                    {{ form.errors.term_count }}
                                </p>
                            </div>

                            <div v-if="form.term_count && form.term_count > 0" class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <Label>{{ t('contracts.term_percentages') }}</Label>
                                    <span :class="['text-sm font-medium', totalPercentage === 100 ? 'text-green-600' : 'text-destructive']">
                                        {{ t('contracts.total') }}: {{ totalPercentage }}%
                                    </span>
                                </div>
                                <div class="grid grid-cols-4 gap-2">
                                    <div v-for="(_, termIdx) in termInputs" :key="termIdx" class="space-y-1">
                                        <Label class="text-xs text-muted-foreground">
                                            {{ t('contracts.term') }} {{ termIdx + 1 }}
                                        </Label>
                                        <div class="relative">
                                            <Input
                                                v-model.number="termInputs[termIdx]"
                                                type="number"
                                                min="0"
                                                max="100"
                                                class="pr-6"
                                                :disabled="form.processing"
                                            />
                                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">%</span>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="form.errors.term_percentages" class="text-sm text-destructive">
                                    {{ form.errors.term_percentages }}
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

        <ActionConfirmationDialog
            v-model:open="saveConfirmation.open.value"
            :title="saveConfirmation.title.value"
            :description="saveConfirmation.description.value"
            :confirm-text="saveConfirmation.confirmText.value"
            :cancel-text="saveConfirmation.cancelText.value"
            :destructive="saveConfirmation.destructive.value"
            :details="saveConfirmation.details.value"
            @confirm="saveConfirmation.confirm"
            @cancel="saveConfirmation.cancel"
        />

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

        <ActionConfirmationDialog
            v-model:open="approverConfirmation.open.value"
            :title="approverConfirmation.title.value"
            :description="approverConfirmation.description.value"
            :confirm-text="approverConfirmation.confirmText.value"
            :cancel-text="approverConfirmation.cancelText.value"
            :destructive="approverConfirmation.destructive.value"
            :details="approverConfirmation.details.value"
            @confirm="approverConfirmation.confirm"
            @cancel="approverConfirmation.cancel"
        />
    </AppLayout>
</template>
