<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, DollarSign, FileText, Plus } from 'lucide-vue-next';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ActionConfirmationDialog from '@/components/ActionConfirmationDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
import { useActionConfirmation } from '@/composables/useActionConfirmation';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store } from '@/routes/tickets';
import type { BreadcrumbItem } from '@/types';
import type {
    ContractOption,
    TicketForm,
    VendorOption,
} from '@/types/models';

const props = defineProps<{
    vendors: VendorOption[];
    contracts: ContractOption[];
}>();

const { t } = useI18n();
const toast = useToast();
const confirmation = useActionConfirmation();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.tickets'), href: index().url },
    { title: t('tickets.create'), href: '#' },
]);

// Filter contracts by vendor
const filteredContracts = computed(() => {
    if (!form.vendor_id) return [];
    return props.contracts.filter((c) => c.vendor_id === form.vendor_id);
});

// Select options
const vendorOptions = computed(() =>
    props.vendors.map((v) => ({ value: v.id, label: `${v.code} - ${v.name}` })),
);

const contractOptions = computed(() =>
    filteredContracts.value.map((c) => ({ value: c.id, label: c.number })),
);

const isContractSelectDisabled = computed(() => form.processing || !form.vendor_id);

// Form state
const form = useForm<TicketForm>({
    date: new Date().toISOString().split('T')[0],
    contract_id: null,
    vendor_id: null,
    amount: null,
    reference_no: '',
    replaces_ticket_id: null,
    notes: '',
    is_active: true,
});

// Selected contract for showing balance info
const selectedContract = computed(() => {
    if (!form.contract_id) return null;
    return props.contracts.find((c) => c.id === form.contract_id) ?? null;
});

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

// When vendor changes, reset contract if it doesn't belong to the vendor
watch(() => form.vendor_id, (vendorId) => {
    if (!vendorId) {
        form.contract_id = null;
        return;
    }

    if (vendorId && form.contract_id) {
        const contract = props.contracts.find((c) => c.id === form.contract_id);
        if (contract && contract.vendor_id !== vendorId) {
            form.contract_id = null;
        }
    }
});

// When contract changes, auto-set vendor
watch(() => form.contract_id, (contractId) => {
    if (contractId) {
        const contract = props.contracts.find((c) => c.id === contractId);
        if (contract) {
            form.vendor_id = contract.vendor_id;
        }
    }
});

function goBack() {
    router.get(index().url);
}

function executeSubmitForm() {
    form.post(store().url, {
        preserveScroll: true,
        onSuccess: (response) => {
            toast.success(t('tickets.created_successfully'));
            // Redirect to edit page to add documents
            const ticketId = (response.props as any)?.ticket?.id;
            if (ticketId) {
                router.visit(`/tickets/${ticketId}/edit`);
            } else {
                router.visit(index().url);
            }
        },
        onError: () => {
            toast.error(t('tickets.create_failed'));
        },
    });
}

function submitForm() {
    confirmation.requestConfirmation(
        {
            title: t('common.create'),
            description: t('tickets.create_description'),
            confirmText: t('tickets.create'),
            details: [
                { label: t('tickets.vendor'), value: vendorOptions.value.find((v) => v.value === form.vendor_id)?.label ?? '-' },
                { label: t('tickets.contract'), value: contractOptions.value.find((c) => c.value === form.contract_id)?.label ?? '-' },
                { label: t('payment_tracker.amount'), value: form.amount ? formatCurrency(form.amount) : '-' },
            ],
        },
        executeSubmitForm,
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('tickets.create')" />
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" @click="goBack">
                    <ArrowLeft class="size-4" />
                </Button>
                <Heading
                    :title="t('tickets.create')"
                    :description="t('tickets.create_description')"
                />
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Ticket Details Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="size-5" />
                            {{ t('tickets.ticket_info') }}
                        </CardTitle>
                        <CardDescription>
                            {{ t('tickets.create_info_description') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label>{{ t('tickets.date') }}</Label>
                                <Input
                                    v-model="form.date"
                                    type="date"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.date" class="text-sm text-destructive">
                                    {{ form.errors.date }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label>{{ t('tickets.vendor') }}</Label>
                                <Select
                                    v-model="form.vendor_id"
                                    :options="vendorOptions"
                                    :placeholder="t('tickets.select_vendor')"
                                    :disabled="form.processing"
                                    clearable
                                />
                                <p v-if="form.errors.vendor_id" class="text-sm text-destructive">
                                    {{ form.errors.vendor_id }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label>{{ t('tickets.contract') }}</Label>
                                <Select
                                    v-model="form.contract_id"
                                    key="ticket-create-contract-select"
                                    :options="contractOptions"
                                    :placeholder="form.vendor_id ? t('tickets.select_contract') : t('tickets.select_vendor')"
                                    :disabled="isContractSelectDisabled"
                                    clearable
                                />
                                <p v-if="form.errors.contract_id" class="text-sm text-destructive">
                                    {{ form.errors.contract_id }}
                                </p>
                            </div>

                            <div class="space-y-2 sm:col-span-2">
                                <Label>{{ t('tickets.notes') }}</Label>
                                <Textarea
                                    v-model="form.notes"
                                    :placeholder="t('tickets.notes_placeholder')"
                                    :disabled="form.processing"
                                    :rows="3"
                                />
                                <p v-if="form.errors.notes" class="text-sm text-destructive">
                                    {{ form.errors.notes }}
                                </p>
                            </div>

                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    id="is_active"
                                    :checked="form.is_active"
                                    @update:checked="form.is_active = $event"
                                    :disabled="form.processing"
                                />
                                <Label for="is_active" class="font-normal">
                                    {{ t('common.active') }}
                                </Label>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Payment Amount Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <DollarSign class="size-5" />
                            {{ t('tickets.payment_amount') }}
                        </CardTitle>
                        <CardDescription>
                            {{ t('tickets.payment_amount_description') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label>{{ t('payment_tracker.amount') }}</Label>
                                <CurrencyInput
                                    v-model="form.amount"
                                    :min="0"
                                    :disabled="form.processing"
                                />
                                <p class="text-xs text-muted-foreground">
                                    {{ t('tickets.amount_hint') }}
                                </p>
                                <p v-if="form.errors.amount" class="text-sm text-destructive">
                                    {{ form.errors.amount }}
                                </p>
                            </div>
                        </div>

                        <!-- Contract Balance Info -->
                        <div v-if="selectedContract" class="rounded-lg border bg-muted/30 p-4">
                            <p class="mb-3 text-sm font-medium">{{ t('payment_tracker.contract_balance') }}</p>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div>
                                    <p class="text-muted-foreground text-xs">{{ t('contracts.amount') }}</p>
                                    <p class="font-semibold">{{ formatCurrency(selectedContract.amount) }}</p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground text-xs">{{ t('contracts.total_paid') }}</p>
                                    <p class="font-semibold text-green-600">{{ formatCurrency(selectedContract.payment_total_paid ?? 0) }}</p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground text-xs">{{ t('contracts.outstanding_balance') }}</p>
                                    <p class="font-semibold text-orange-600">{{ formatCurrency(selectedContract.payment_balance ?? selectedContract.amount) }}</p>
                                </div>
                            </div>
                            <div v-if="form.amount && form.amount > (selectedContract.payment_balance ?? selectedContract.amount)" class="mt-3">
                                <Badge variant="destructive">{{ t('tickets.amount_exceeds_balance') }}</Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Info Card about documents -->
                <Card class="border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950">
                    <CardContent class="p-4">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            {{ t('tickets.create_document_info') }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Submit buttons -->
                <div class="flex items-center justify-end gap-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="goBack"
                        :disabled="form.processing"
                    >
                        {{ t('common.cancel') }}
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Plus class="mr-2 size-4" />
                        {{ form.processing ? t('common.creating') : t('tickets.create') }}
                    </Button>
                </div>
            </form>
        </div>

        <ActionConfirmationDialog
            v-model:open="confirmation.open.value"
            :title="confirmation.title.value"
            :description="confirmation.description.value"
            :confirm-text="confirmation.confirmText.value"
            :cancel-text="confirmation.cancelText.value"
            :destructive="confirmation.destructive.value"
            :details="confirmation.details.value"
            @confirm="confirmation.confirm"
            @cancel="confirmation.cancel"
        />
    </AppLayout>
</template>
