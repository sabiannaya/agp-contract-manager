<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, CheckCircle2, XCircle, Clock, DollarSign, FileText, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import ActionConfirmationDialog from '@/components/ActionConfirmationDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { StatusBadge } from '@/components/ui/status-badge';
import { useActionConfirmation } from '@/composables/useActionConfirmation';
import { useDateFormat } from '@/composables/useDateFormat';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import type { Ticket } from '@/types/models';

const props = defineProps<{
    ticket: Ticket;
}>();

const { t } = useI18n();
const { formatDateOnly, formatDateTime } = useDateFormat();
const toast = useToast();
const submitConfirmation = useActionConfirmation();

const page = usePage<AppPageProps>();
const currentUserId = computed(() => page.props.auth.user.id);

// Determine the next pending approval step (lowest sequence_no with status 'pending')
const nextPendingStepId = computed(() => {
    const steps = (props.ticket as any).approval_steps ?? [];
    const pendingSteps = steps.filter((s: any) => s.status === 'pending');
    if (pendingSteps.length === 0) return null;
    pendingSteps.sort((a: any, b: any) => a.sequence_no - b.sequence_no);
    return pendingSteps[0].id;
});

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('nav.payment_tracker'), href: '/payment-tracker' },
    { title: props.ticket.number, href: '#' },
];

function goBack() {
    router.get('/payment-tracker');
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

function stepStatusIcon(status: string) {
    switch (status) {
        case 'approved': return CheckCircle2;
        case 'rejected': return XCircle;
        default: return Clock;
    }
}

function stepStatusColor(status: string): string {
    switch (status) {
        case 'approved': return 'text-green-600';
        case 'rejected': return 'text-red-600';
        default: return 'text-muted-foreground';
    }
}

function getApproverConfigRemark(step: any): string | null {
    const approvers = ticket.contract?.approvers ?? [];
    const matched = approvers.find((a: any) =>
        a.user_id === step.approver_user_id && a.sequence_no === step.sequence_no,
    );

    return matched?.remarks ?? null;
}

// Approval action modals
const isApproveModalOpen = ref(false);
const isRejectModalOpen = ref(false);
const isPaidModalOpen = ref(false);

const approveForm = useForm({ remarks: '' });
const rejectForm = useForm({ remarks: '' });
const paidForm = useForm({ reference_no: '' });

function submitApproval() {
    approveForm.post(`/payment-tracker/${props.ticket.id}/approve`, {
        onSuccess: () => {
            isApproveModalOpen.value = false;
            toast.success(t('payment_tracker.approved_successfully'));
            router.reload();
        },
        onError: () => {
            isApproveModalOpen.value = false;
            toast.error(t('payment_tracker.approve_failed'));
        },
    });
}

function submitRejection() {
    rejectForm.post(`/payment-tracker/${props.ticket.id}/reject`, {
        onSuccess: () => {
            isRejectModalOpen.value = false;
            toast.success(t('payment_tracker.rejected_successfully'));
            router.reload();
        },
        onError: () => {
            isRejectModalOpen.value = false;
            toast.error(t('payment_tracker.reject_failed'));
        },
    });
}

function submitMarkPaid() {
    paidForm.post(`/payment-tracker/${props.ticket.id}/mark-paid`, {
        onSuccess: () => {
            isPaidModalOpen.value = false;
            toast.success(t('payment_tracker.paid_successfully'));
            router.reload();
        },
        onError: () => {
            isPaidModalOpen.value = false;
            toast.error(t('payment_tracker.paid_failed'));
        },
    });
}

function submitForApproval() {
    submitConfirmation.requestConfirmation(
        {
            title: t('payment_tracker.confirm_submit'),
            description: t('payment_tracker.submit_description'),
            confirmText: t('payment_tracker.submit_for_approval'),
            cancelText: t('common.cancel'),
        },
        () => executeSubmitForApproval(),
    );
}

function executeSubmitForApproval() {
    router.post(`/payment-tracker/${props.ticket.id}/submit`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('payment_tracker.submitted_successfully'));
            router.reload();
        },
        onError: () => {
            toast.error(t('payment_tracker.submit_failed'));
        },
    });
}

const ticket = props.ticket as any;
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`${t('nav.payment_tracker')} - ${ticket.number}`" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="icon" @click="goBack">
                        <ArrowLeft class="size-4" />
                    </Button>
                    <Heading :title="ticket.number" :description="t('payment_tracker.ticket_payment_detail')" />
                </div>
                <div class="flex items-center gap-2">
                    <Badge :variant="approvalStatusVariant(ticket.approval_status)">
                        {{ t(`payment_tracker.statuses.${ticket.approval_status}`) }}
                    </Badge>

                    <Button v-if="ticket.approval_status === 'draft'" @click="submitForApproval">
                        {{ t('payment_tracker.submit_for_approval') }}
                    </Button>
                    <Button v-if="ticket.approval_status === 'approved'" variant="default" @click="isPaidModalOpen = true">
                        <DollarSign class="mr-2 size-4" />
                        {{ t('payment_tracker.mark_paid') }}
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Ticket/Payment Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('payment_tracker.payment_info') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('tickets.number') }}</p>
                                <p class="font-medium">{{ ticket.number }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('tickets.date') }}</p>
                                <p class="font-medium">{{ formatDateOnly(ticket.date) }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('payment_tracker.amount') }}</p>
                                <p class="text-lg font-semibold">{{ formatCurrency(ticket.amount) }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('tickets.contract') }}</p>
                                <p class="font-medium">{{ ticket.contract?.number ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('tickets.vendor') }}</p>
                                <p class="font-medium">{{ ticket.vendor?.name ?? '-' }}</p>
                            </div>
                            <div v-if="ticket.reference_no">
                                <p class="text-muted-foreground text-sm">{{ t('payment_tracker.reference_no') }}</p>
                                <p class="font-medium">{{ ticket.reference_no }}</p>
                            </div>
                            <div v-if="ticket.replaces_ticket">
                                <p class="text-muted-foreground text-sm">{{ t('payment_tracker.replaces') }}</p>
                                <p class="font-medium">{{ ticket.replaces_ticket.number }}</p>
                            </div>
                        </div>
                        <div v-if="ticket.notes" class="border-t pt-4">
                            <p class="text-muted-foreground text-sm">{{ t('tickets.notes') }}</p>
                            <p class="mt-1">{{ ticket.notes }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Contract Balance -->
                <Card v-if="ticket.contract">
                    <CardHeader>
                        <CardTitle>{{ t('payment_tracker.contract_balance') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('contracts.amount') }}</p>
                                <p class="text-lg font-semibold">{{ formatCurrency(ticket.contract.amount) }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('contracts.total_paid') }}</p>
                                <p class="text-lg font-semibold text-green-600">{{ formatCurrency(ticket.contract.payment_total_paid ?? 0) }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('contracts.outstanding_balance') }}</p>
                                <p class="text-lg font-semibold text-orange-600">{{ formatCurrency(ticket.contract.payment_balance ?? ticket.contract.amount) }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('contracts.cooperation_type') }}</p>
                                <Badge :variant="ticket.contract.cooperation_type === 'progress' ? 'default' : 'secondary'">
                                    {{ t(`contracts.types.${ticket.contract.cooperation_type}`) }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Approval Steps Timeline -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="size-5" />
                        {{ t('payment_tracker.approval_steps') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="ticket.approval_steps?.length" class="space-y-4">
                        <div
                            v-for="step in ticket.approval_steps"
                            :key="step.id"
                            class="flex items-start gap-4 rounded-lg border p-4"
                        >
                            <component
                                :is="stepStatusIcon(step.status)"
                                class="mt-0.5 size-5 shrink-0"
                                :class="stepStatusColor(step.status)"
                            />
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="font-medium">
                                        #{{ step.sequence_no }} — {{ step.approver?.name ?? 'Unknown' }}
                                    </p>
                                    <Badge :variant="approvalStatusVariant(step.status)">
                                        {{ t(`payment_tracker.statuses.${step.status}`) }}
                                    </Badge>
                                </div>
                                <p v-if="step.acted_at" class="text-muted-foreground text-sm">
                                    {{ formatDateTime(step.acted_at) }}
                                </p>
                                <p v-if="getApproverConfigRemark(step)" class="mt-1 text-xs text-muted-foreground">
                                    Reason: {{ getApproverConfigRemark(step) }}
                                </p>
                                <p v-if="step.remarks" class="mt-1 text-sm">{{ step.remarks }}</p>

                                <!-- Action buttons — only for the designated approver whose turn it is -->
                                <div v-if="step.status === 'pending' && ticket.approval_status === 'pending' && step.approver_user_id === currentUserId && step.id === nextPendingStepId" class="mt-3 flex gap-2">
                                    <Button size="sm" @click="isApproveModalOpen = true">
                                        <CheckCircle2 class="mr-1 size-4" />
                                        {{ t('payment_tracker.approve') }}
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="isRejectModalOpen = true">
                                        <XCircle class="mr-1 size-4" />
                                        {{ t('payment_tracker.reject') }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-muted-foreground py-8 text-center">
                        <Clock class="mx-auto mb-2 size-8 opacity-50" />
                        <p>{{ t('payment_tracker.no_approval_steps') }}</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Approve Modal -->
        <Dialog v-model:open="isApproveModalOpen">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>{{ t('payment_tracker.confirm_approve') }}</DialogTitle>
                    <DialogDescription>{{ t('payment_tracker.approve_description') }}</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitApproval" class="space-y-4">
                    <div class="space-y-2">
                        <Label>{{ t('payment_tracker.remarks') }}</Label>
                        <Input v-model="approveForm.remarks" :placeholder="t('payment_tracker.remarks_placeholder')" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isApproveModalOpen = false">{{ t('common.cancel') }}</Button>
                        <Button type="submit" :disabled="approveForm.processing">{{ t('payment_tracker.approve') }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Reject Modal -->
        <Dialog v-model:open="isRejectModalOpen">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>{{ t('payment_tracker.confirm_reject') }}</DialogTitle>
                    <DialogDescription>{{ t('payment_tracker.reject_description') }}</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitRejection" class="space-y-4">
                    <div class="space-y-2">
                        <Label>{{ t('payment_tracker.remarks') }} *</Label>
                        <Input v-model="rejectForm.remarks" :placeholder="t('payment_tracker.remarks_placeholder')" required />
                        <p v-if="rejectForm.errors.remarks" class="text-sm text-destructive">{{ rejectForm.errors.remarks }}</p>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isRejectModalOpen = false">{{ t('common.cancel') }}</Button>
                        <Button type="submit" variant="destructive" :disabled="rejectForm.processing">{{ t('payment_tracker.reject') }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Submit Confirmation Dialog -->
        <ActionConfirmationDialog
            v-model:open="submitConfirmation.open.value"
            :title="submitConfirmation.title.value"
            :description="submitConfirmation.description.value"
            :confirm-text="submitConfirmation.confirmText.value"
            :cancel-text="submitConfirmation.cancelText.value"
            :destructive="submitConfirmation.destructive.value"
            :details="submitConfirmation.details.value"
            @confirm="submitConfirmation.confirm"
            @cancel="submitConfirmation.cancel"
        />

        <!-- Mark Paid Modal -->
        <Dialog v-model:open="isPaidModalOpen">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>{{ t('payment_tracker.confirm_paid') }}</DialogTitle>
                    <DialogDescription>{{ t('payment_tracker.paid_description') }}</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitMarkPaid" class="space-y-4">
                    <div class="space-y-2">
                        <Label>{{ t('payment_tracker.reference_no') }}</Label>
                        <Input v-model="paidForm.reference_no" :placeholder="t('payment_tracker.reference_placeholder')" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isPaidModalOpen = false">{{ t('common.cancel') }}</Button>
                        <Button type="submit" :disabled="paidForm.processing">{{ t('payment_tracker.mark_paid') }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
