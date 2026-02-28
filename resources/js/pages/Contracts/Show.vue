<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, FileText, Pencil, Plus, ShieldCheck, Trash2, User } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ActionConfirmationDialog from '@/components/ActionConfirmationDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CurrencyInput } from '@/components/ui/currency-input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { StatusBadge } from '@/components/ui/status-badge';
import { useActionConfirmation } from '@/composables/useActionConfirmation';
import { useDateFormat } from '@/composables/useDateFormat';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editRoute, index, update } from '@/routes/contracts';
import { show as showTicket } from '@/routes/tickets';
import type { BreadcrumbItem } from '@/types';
import type { Contract, VendorOption } from '@/types/models';
import { list as vendorList } from '@/routes/vendors';

interface EligibleUser {
    id: number;
    name: string;
    email: string;
}

const props = defineProps<{
    contract: Contract;
    eligibleMasters?: EligibleUser[];
}>();

const { t } = useI18n();
const { formatDateOnly, formatDateTime } = useDateFormat();
const { canUpdate } = usePermission();
const saveConfirmation = useActionConfirmation();
const approverConfirmation = useActionConfirmation();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('nav.contracts'), href: index().url },
    { title: props.contract.number, href: '#' },
];

function goBack() {
    router.get(index().url);
}

function goEditPage() {
    router.get(editRoute(props.contract.id).url);
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

function viewTicket(ticketId: number) {
    router.get(showTicket(ticketId).url);
}

// ====== Edit modal (modal-only approach) ======
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

const approverForm = useForm<{ approvers: ApproverRow[] }>({
    approvers: [],
});

function hydrateApproverForm() {
    approverForm.approvers = ((props.contract as any).approvers ?? []).map((approver: any, index: number) => ({
        user_id: approver.user_id ?? null,
        sequence_no: approver.sequence_no ?? index + 1,
        remarks: approver.remarks ?? '',
    }));

    if (approverForm.approvers.length === 0) {
        approverForm.approvers = [{ user_id: null, sequence_no: 1, remarks: '' }];
    }
}

function normalizeSequenceNumbers() {
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
    normalizeSequenceNumbers();
}

function executeSaveApprovers() {
    normalizeSequenceNumbers();

    approverForm.post(`/contracts/${props.contract.id}/approvers`, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['contract'] });
        },
    });
}

function submitApprovers() {
    approverConfirmation.requestConfirmation(
        {
            title: t('contracts.approvers'),
            description: 'Update contract approvers and why they are required in the workflow.',
            confirmText: t('common.save'),
            details: [
                { label: t('contracts.contract_number'), value: props.contract.number },
                { label: t('contracts.approvers'), value: String(approverForm.approvers.length) },
            ],
        },
        executeSaveApprovers,
    );
}

watch(
    () => (props.contract as any).approvers,
    () => {
        hydrateApproverForm();
    },
    { immediate: true },
);

const isEditModalOpen = ref(false);

const form = useForm({
    number: props.contract.number,
    date: props.contract.date,
    vendor_id: props.contract.vendor_id as number | null,
    amount: Number(props.contract.amount ?? 0),
    cooperation_type: props.contract.cooperation_type,
    term_count: props.contract.term_count,
    term_percentages: props.contract.term_percentages ?? [] as number[],
    is_active: props.contract.is_active,
    assigned_master_user_id: (props.contract as any).assigned_master_user_id ?? null as number | null,
});

const termInputs = ref<number[]>(props.contract.term_percentages ?? []);

watch(() => form.term_count, (count) => {
    if (count && count > 0) {
        const current = [...termInputs.value];
        if (count > current.length) {
            for (let i = current.length; i < count; i++) {
                current.push(0);
            }
        } else {
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

function openEditModal() {
    form.number = props.contract.number;
    form.date = props.contract.date;
    form.vendor_id = props.contract.vendor_id;
    form.amount = props.contract.amount;
    form.cooperation_type = props.contract.cooperation_type;
    form.term_count = props.contract.term_count;
    form.term_percentages = props.contract.term_percentages ?? [];
    termInputs.value = props.contract.term_percentages ?? [];
    form.is_active = props.contract.is_active;
    form.assigned_master_user_id = (props.contract as any).assigned_master_user_id ?? null;
    form.clearErrors();
    isEditModalOpen.value = true;
}

function executeSubmitEdit() {
    form.put(update(props.contract.id).url, {
        onSuccess: () => {
            isEditModalOpen.value = false;
        },
    });
}

function submitEdit() {
    saveConfirmation.requestConfirmation(
        {
            title: t('common.update'),
            description: t('contracts.edit_description'),
            confirmText: t('common.save'),
            details: [
                { label: t('contracts.number'), value: form.number || '-' },
                { label: t('contracts.vendor'), value: vendorOptions.value.find((v) => v.value === form.vendor_id)?.label ?? '-' },
                { label: t('contracts.amount'), value: formatCurrency(form.amount ?? 0) },
            ],
        },
        executeSubmitEdit,
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`${t('contracts.contract')} - ${contract.number}`" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="icon" @click="goBack">
                        <ArrowLeft class="size-4" />
                    </Button>
                    <Heading :title="contract.number" :description="t('contracts.contract_details')" />
                </div>
                <div class="flex items-center gap-2">
                    <StatusBadge :active="contract.is_active" />
                    <Button v-if="canUpdate('contracts')" @click="goEditPage">
                        <Pencil class="mr-2 size-4" />
                        {{ t('common.edit') }}
                    </Button>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Contract Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('contracts.contract_info') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('contracts.contract_number') }}</p>
                                <p class="font-medium">{{ contract.number }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('contracts.date') }}</p>
                                <p class="font-medium">{{ formatDateOnly(contract.date) }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('contracts.amount') }}</p>
                                <p class="text-lg font-semibold">{{ formatCurrency(contract.amount) }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('contracts.cooperation_type') }}</p>
                                <Badge :variant="contract.cooperation_type === 'progress' ? 'default' : 'secondary'">
                                    {{ t(`contracts.cooperation_types.${contract.cooperation_type}`) }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Term Details for Progress Type -->
                        <div v-if="contract.cooperation_type === 'progress'" class="border-t pt-4">
                            <p class="text-muted-foreground mb-2 text-sm">{{ t('contracts.term_percentages') }}</p>
                            <div v-if="contract.term_percentages?.length" class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="(percentage, index) in contract.term_percentages"
                                    :key="index"
                                    variant="outline"
                                >
                                    {{ t('contracts.term_n', { n: index + 1 }) }}: {{ percentage }}%
                                </Badge>
                            </div>
                            <p v-else class="text-muted-foreground text-sm">-</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Vendor Information -->
                <Card v-if="contract.vendor">
                    <CardHeader>
                        <CardTitle>{{ t('contracts.vendor_info') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('vendors.code') }}</p>
                                <p class="font-medium">{{ contract.vendor.code }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('vendors.name') }}</p>
                                <p class="font-medium">{{ contract.vendor.name }}</p>
                            </div>
                            <div v-if="contract.vendor.address" class="sm:col-span-2">
                                <p class="text-muted-foreground text-sm">{{ t('vendors.address') }}</p>
                                <p class="font-medium">{{ contract.vendor.address }}</p>
                            </div>
                            <div v-if="contract.vendor.contact_person">
                                <p class="text-muted-foreground text-sm">{{ t('vendors.contact_person') }}</p>
                                <p class="font-medium">{{ contract.vendor.contact_person }}</p>
                            </div>
                            <div v-if="contract.vendor.tax_id">
                                <p class="text-muted-foreground text-sm">{{ t('vendors.tax_id') }}</p>
                                <p class="font-medium">{{ contract.vendor.tax_id }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tickets List -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="size-5" />
                        {{ t('contracts.related_tickets') }}
                        <Badge variant="secondary">{{ contract.tickets?.length || 0 }}</Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="contract.tickets?.length" class="divide-y">
                        <div
                            v-for="ticket in contract.tickets"
                            :key="ticket.id"
                            class="flex cursor-pointer items-center justify-between py-4 transition-colors hover:bg-muted/50"
                            @click="viewTicket(ticket.id)"
                        >
                            <div class="flex items-center gap-4">
                                <div>
                                    <p class="font-medium">{{ ticket.number }}</p>
                                    <p class="text-muted-foreground text-sm">
                                        <Calendar class="mr-1 inline size-3" />
                                        {{ formatDateOnly(ticket.date) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <Badge v-if="ticket.documents?.length" variant="outline">
                                    {{ ticket.documents.length }} {{ t('documents.documents') }}
                                </Badge>
                                <StatusBadge
                                    :active="ticket.status === 'complete'"
                                    :active-text="t('tickets.status_types.complete')"
                                    :inactive-text="t('tickets.status_types.incomplete')"
                                />
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-muted-foreground py-8 text-center">
                        <FileText class="mx-auto mb-2 size-8 opacity-50" />
                        <p>{{ t('contracts.no_tickets') }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Audit Information -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <User class="size-5" />
                        {{ t('common.audit_info') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p class="text-muted-foreground text-sm">{{ t('common.created_by') }}</p>
                            <p class="font-medium">{{ contract.created_by?.name || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-sm">{{ t('common.created_at') }}</p>
                            <p class="font-medium">{{ formatDateTime(contract.created_at) }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-sm">{{ t('common.updated_by') }}</p>
                            <p class="font-medium">{{ contract.updated_by?.name || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-sm">{{ t('common.updated_at') }}</p>
                            <p class="font-medium">{{ formatDateTime(contract.updated_at) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Payment Summary -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ShieldCheck class="size-5" />
                        {{ t('contracts.payment_summary') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p class="text-muted-foreground text-sm">{{ t('contracts.amount') }}</p>
                            <p class="text-lg font-semibold">{{ formatCurrency(contract.amount) }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-sm">{{ t('contracts.total_paid') }}</p>
                            <p class="text-lg font-semibold text-green-600">{{ formatCurrency((contract as any).payment_total_paid ?? 0) }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-sm">{{ t('contracts.outstanding_balance') }}</p>
                            <p class="text-lg font-semibold text-orange-600">{{ formatCurrency((contract as any).payment_balance ?? contract.amount) }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-sm">{{ t('contracts.contract_master') }}</p>
                            <div class="space-y-1">
                                <p class="font-medium">{{ contract.created_by?.name || '-' }} <Badge variant="outline" class="ml-1 text-xs">{{ t('contracts.creator') }}</Badge></p>
                                <p v-if="(contract as any).assigned_master" class="font-medium">{{ (contract as any).assigned_master?.name }} <Badge variant="outline" class="ml-1 text-xs">{{ t('contracts.assigned') }}</Badge></p>
                            </div>
                        </div>
                    </div>

                    <!-- Approvers list -->
                    <div v-if="(contract as any).approvers?.length" class="mt-4 border-t pt-4">
                        <p class="text-muted-foreground mb-2 text-sm font-medium">{{ t('contracts.approvers') }}</p>
                        <div class="space-y-2">
                            <div
                                v-for="approver in (contract as any).approvers"
                                :key="approver.id"
                                class="rounded-md border p-2"
                            >
                                <Badge variant="secondary">
                                    #{{ approver.sequence_no }} {{ approver.user?.name }}
                                </Badge>
                                <p v-if="approver.remarks" class="mt-1 text-xs text-muted-foreground">
                                    {{ approver.remarks }}
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center justify-between gap-2">
                        <span>{{ t('contracts.approvers') }}</span>
                        <Button type="button" variant="outline" size="sm" @click="addApproverRow" :disabled="approverForm.processing">
                            <Plus class="mr-1 size-4" />
                            {{ t('common.create') }}
                        </Button>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form class="space-y-3" @submit.prevent="submitApprovers">
                        <div
                            v-for="(row, index) in approverForm.approvers"
                            :key="`approver-row-${index}`"
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
                            <Button type="submit" :disabled="approverForm.processing">
                                {{ approverForm.processing ? t('common.saving') : t('common.save') }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>

        <!-- Edit Modal -->
        <Dialog v-model:open="isEditModalOpen">
            <DialogContent class="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>{{ t('contracts.edit') }}</DialogTitle>
                    <DialogDescription>{{ t('contracts.edit_description') }}</DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="grid gap-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label>{{ t('contracts.number') }}</Label>
                                <Input v-model="form.number" :disabled="form.processing" />
                                <p v-if="form.errors.number" class="text-sm text-destructive">{{ form.errors.number }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>{{ t('contracts.date') }}</Label>
                                <Input v-model="form.date" type="date" :disabled="form.processing" />
                                <p v-if="form.errors.date" class="text-sm text-destructive">{{ form.errors.date }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label>{{ t('contracts.vendor') }}</Label>
                            <Select v-model="form.vendor_id" :options="vendorOptions" :placeholder="t('contracts.select_vendor')" :disabled="form.processing" clearable />
                            <p v-if="form.errors.vendor_id" class="text-sm text-destructive">{{ form.errors.vendor_id }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label>{{ t('contracts.amount') }}</Label>
                                <CurrencyInput v-model="form.amount" :min="0" :disabled="form.processing" />
                                <p v-if="form.errors.amount" class="text-sm text-destructive">{{ form.errors.amount }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>{{ t('contracts.cooperation_type') }}</Label>
                                <Select v-model="form.cooperation_type" :options="cooperationTypeOptions" :disabled="form.processing" />
                                <p v-if="form.errors.cooperation_type" class="text-sm text-destructive">{{ form.errors.cooperation_type }}</p>
                            </div>
                        </div>

                        <!-- Assigned Contract Master -->
                        <div class="space-y-2">
                            <Label>{{ t('contracts.assigned_master') }}</Label>
                            <Select v-model="form.assigned_master_user_id" :options="masterOptions" :placeholder="t('contracts.select_master')" :disabled="form.processing" clearable />
                            <p class="text-xs text-muted-foreground">{{ t('contracts.master_hint') }}</p>
                            <p v-if="form.errors.assigned_master_user_id" class="text-sm text-destructive">{{ form.errors.assigned_master_user_id }}</p>
                        </div>

                        <!-- Term fields for progress type -->
                        <div v-if="form.cooperation_type === 'progress'" class="space-y-4 border rounded-lg p-4">
                            <div class="space-y-2">
                                <Label>{{ t('contracts.term_count') }}</Label>
                                <Input :model-value="form.term_count ?? undefined" @update:model-value="form.term_count = $event ? Number($event) : null" type="number" min="1" max="12" :disabled="form.processing" />
                                <p v-if="form.errors.term_count" class="text-sm text-destructive">{{ form.errors.term_count }}</p>
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
                                        <Label class="text-xs text-muted-foreground">{{ t('contracts.term') }} {{ termIdx + 1 }}</Label>
                                        <div class="relative">
                                            <Input v-model.number="termInputs[termIdx]" type="number" min="0" max="100" class="pr-6" :disabled="form.processing" />
                                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">%</span>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="form.errors.term_percentages" class="text-sm text-destructive">{{ form.errors.term_percentages }}</p>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isEditModalOpen = false" :disabled="form.processing">
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
