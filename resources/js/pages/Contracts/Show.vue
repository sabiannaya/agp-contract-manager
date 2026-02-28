<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, FileText, Pencil, Plus, ShieldCheck, Trash2, User } from 'lucide-vue-next';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ActionConfirmationDialog from '@/components/ActionConfirmationDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { StatusBadge } from '@/components/ui/status-badge';
import { useActionConfirmation } from '@/composables/useActionConfirmation';
import { useDateFormat } from '@/composables/useDateFormat';
import { useToast } from '@/composables/useToast';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editRoute, index } from '@/routes/contracts';
import { show as showTicket } from '@/routes/tickets';
import type { BreadcrumbItem } from '@/types';
import type { Contract } from '@/types/models';

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
const toast = useToast();
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

// ====== Approvers management ======
const masterOptions = computed(() =>
    (props.eligibleMasters ?? []).map((u) => ({ value: u.id, label: `${u.name} (${u.email})` })),
);

const approverOptions = computed(() => masterOptions.value);

type ApproverRow = {
    user_id: number | null;
    sequence_no: number;
    remarks: string;
    is_master: boolean;
};

const approverForm = useForm<{ approvers: ApproverRow[] }>({
    approvers: [],
});

function hydrateApproverForm() {
    const existing = ((props.contract as any).approvers ?? []).map((approver: any, index: number) => ({
        user_id: approver.user_id ?? null,
        sequence_no: approver.sequence_no ?? index + 1,
        remarks: approver.remarks ?? '',
        is_master: approver.is_master ?? false,
    }));

    // Backend always seeds masters as approvers, so list should never be empty.
    // Fallback to a blank row only as a safety net.
    approverForm.approvers = existing.length > 0
        ? existing
        : [{ user_id: null, sequence_no: 1, remarks: '', is_master: false }];
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
        is_master: false,
    });
}

function removeApproverRow(index: number) {
    if (approverForm.approvers[index]?.is_master) return;
    approverForm.approvers.splice(index, 1);
    normalizeSequenceNumbers();
}

function executeSaveApprovers() {
    normalizeSequenceNumbers();

    // Only send non-master approvers; masters are managed by the backend
    const nonMasterApprovers = approverForm.approvers
        .filter((row) => !row.is_master)
        .map((row, index) => ({
            user_id: row.user_id,
            sequence_no: index + 1,
            remarks: row.remarks,
        }));

    router.post(`/contracts/${props.contract.id}/approvers`, {
        approvers: nonMasterApprovers,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('contracts.approvers_updated_successfully'));
            router.reload({ only: ['contract'] });
        },
        onError: () => {
            toast.error(t('contracts.approvers_update_failed'));
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
                            :class="['grid gap-3 rounded-md border p-3 lg:grid-cols-12', row.is_master ? 'bg-muted/40' : '']"
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
                                    :disabled="approverForm.processing || row.is_master"
                                    :clearable="!row.is_master"
                                />
                            </div>
                            <div class="lg:col-span-6">
                                <Label>Remarks</Label>
                                <Input
                                    v-model="row.remarks"
                                    :placeholder="'Why this approver is required'"
                                    :disabled="approverForm.processing || row.is_master"
                                />
                                <p v-if="row.is_master" class="text-xs text-muted-foreground mt-1">
                                    <ShieldCheck class="inline size-3 mr-0.5" /> {{ t('contracts.contract_master') }}
                                </p>
                            </div>
                            <div class="flex items-end lg:col-span-1">
                                <Button
                                    v-if="!row.is_master"
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    :disabled="approverForm.processing"
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
