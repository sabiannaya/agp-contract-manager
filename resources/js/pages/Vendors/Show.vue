<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Building2, Calendar, FileText, Pencil, User } from 'lucide-vue-next';
import { ref } from 'vue';
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
import { StatusBadge } from '@/components/ui/status-badge';
import { useActionConfirmation } from '@/composables/useActionConfirmation';
import { useDateFormat } from '@/composables/useDateFormat';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as showContract } from '@/routes/contracts';
import { show as showTicket } from '@/routes/tickets';
import { index, update } from '@/routes/vendors';
import type { BreadcrumbItem } from '@/types';
import type { Vendor, VendorForm } from '@/types/models';

const props = defineProps<{
    vendor: Vendor;
}>();

const { t } = useI18n();
const { formatDateOnly, formatDateTime } = useDateFormat();
const { canUpdate } = usePermission();
const saveConfirmation = useActionConfirmation();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('nav.vendors'), href: index().url },
    { title: props.vendor.name, href: '#' },
];

// Modal state
const isFormModalOpen = ref(false);

function normalizeDate(value: string | null | undefined): string {
    if (!value) return '';
    if (value.includes('T')) return value.split('T')[0];
    if (value.includes(' ')) return value.split(' ')[0];
    return value;
}

const form = useForm<VendorForm>({
    code: props.vendor.code,
    name: props.vendor.name,
    address: props.vendor.address,
    join_date: normalizeDate(props.vendor.join_date),
    contact_person: props.vendor.contact_person,
    tax_id: props.vendor.tax_id,
    is_active: props.vendor.is_active,
});

function goBack() {
    router.get(index().url);
}

function editVendor() {
    form.code = props.vendor.code;
    form.name = props.vendor.name;
    form.address = props.vendor.address;
    form.join_date = normalizeDate(props.vendor.join_date);
    form.contact_person = props.vendor.contact_person;
    form.tax_id = props.vendor.tax_id;
    form.is_active = props.vendor.is_active;
    form.clearErrors();
    isFormModalOpen.value = true;
}

function executeSubmitForm() {
    form.put(update(props.vendor.id).url, {
        onSuccess: () => {
            isFormModalOpen.value = false;
        },
    });
}

function submitForm() {
    saveConfirmation.requestConfirmation(
        {
            title: t('common.update'),
            description: t('vendors.edit_description'),
            confirmText: t('common.save'),
            details: [
                { label: t('vendors.code'), value: form.code || '-' },
                { label: t('vendors.name'), value: form.name || '-' },
            ],
        },
        executeSubmitForm,
    );
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

function viewContract(contractId: number) {
    router.get(showContract(contractId).url);
}

function viewTicket(ticketId: number) {
    router.get(showTicket(ticketId).url);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`${t('vendors.vendor')} - ${vendor.name}`" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="icon" @click="goBack">
                        <ArrowLeft class="size-4" />
                    </Button>
                    <Heading :title="vendor.name" :description="t('vendors.vendor_details')" />
                </div>
                <div class="flex items-center gap-2">
                    <StatusBadge :active="vendor.is_active" />
                    <Button v-if="canUpdate('vendors')" @click="editVendor">
                        <Pencil class="mr-2 size-4" />
                        {{ t('common.edit') }}
                    </Button>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Vendor Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Building2 class="size-5" />
                            {{ t('vendors.vendor_info') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('vendors.code') }}</p>
                                <p class="font-medium">{{ vendor.code }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('vendors.name') }}</p>
                                <p class="font-medium">{{ vendor.name }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-muted-foreground text-sm">{{ t('vendors.address') }}</p>
                                <p class="font-medium">{{ vendor.address || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('vendors.contact_person') }}</p>
                                <p class="font-medium">{{ vendor.contact_person || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('vendors.phone') }}</p>
                                <p class="font-medium">{{ vendor.phone || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('vendors.email') }}</p>
                                <p class="font-medium">{{ vendor.email || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground text-sm">{{ t('vendors.tax_id') }}</p>
                                <p class="font-medium">{{ vendor.tax_id || '-' }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Statistics -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('common.statistics') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-lg border p-4 text-center">
                                <p class="text-3xl font-bold">{{ vendor.contracts?.length || 0 }}</p>
                                <p class="text-muted-foreground text-sm">{{ t('nav.contracts') }}</p>
                            </div>
                            <div class="rounded-lg border p-4 text-center">
                                <p class="text-3xl font-bold">{{ vendor.tickets?.length || 0 }}</p>
                                <p class="text-muted-foreground text-sm">{{ t('nav.tickets') }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Contracts List -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="size-5" />
                        {{ t('vendors.related_contracts') }}
                        <Badge variant="secondary">{{ vendor.contracts?.length || 0 }}</Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="vendor.contracts?.length" class="divide-y">
                        <div
                            v-for="contract in vendor.contracts"
                            :key="contract.id"
                            class="flex cursor-pointer items-center justify-between py-4 transition-colors hover:bg-muted/50"
                            @click="viewContract(contract.id)"
                        >
                            <div class="flex items-center gap-4">
                                <div>
                                    <p class="font-medium">{{ contract.number }}</p>
                                    <p class="text-muted-foreground text-sm">
                                        <Calendar class="mr-1 inline size-3" />
                                        {{ formatDateOnly(contract.date) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="font-medium">{{ formatCurrency(contract.amount) }}</span>
                                <Badge :variant="contract.cooperation_type === 'progress' ? 'default' : 'secondary'">
                                    {{ t(`contracts.cooperation_types.${contract.cooperation_type}`) }}
                                </Badge>
                                <StatusBadge :active="contract.is_active" />
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-muted-foreground py-8 text-center">
                        <FileText class="mx-auto mb-2 size-8 opacity-50" />
                        <p>{{ t('vendors.no_contracts') }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Tickets -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="size-5" />
                        {{ t('vendors.related_tickets') }}
                        <Badge variant="secondary">{{ vendor.tickets?.length || 0 }}</Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="vendor.tickets?.length" class="divide-y">
                        <div
                            v-for="ticket in vendor.tickets.slice(0, 10)"
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
                            <StatusBadge
                                :active="ticket.status === 'complete'"
                                :active-text="t('tickets.status_types.complete')"
                                :inactive-text="t('tickets.status_types.incomplete')"
                            />
                        </div>
                    </div>
                    <div v-else class="text-muted-foreground py-8 text-center">
                        <FileText class="mx-auto mb-2 size-8 opacity-50" />
                        <p>{{ t('vendors.no_tickets') }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Audit Information -->

        <!-- Edit Modal -->
        <Dialog v-model:open="isFormModalOpen">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>{{ t('vendors.edit') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('vendors.edit_description') }}
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
                            <p class="font-medium">{{ vendor.created_by?.name || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-sm">{{ t('common.created_at') }}</p>
                            <p class="font-medium">{{ formatDateTime(vendor.created_at) }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-sm">{{ t('common.updated_by') }}</p>
                            <p class="font-medium">{{ vendor.updated_by?.name || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-sm">{{ t('common.updated_at') }}</p>
                            <p class="font-medium">{{ formatDateTime(vendor.updated_at) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <ActionConfirmationDialog
            v-model:open="saveConfirmation.open"
            :title="saveConfirmation.title"
            :description="saveConfirmation.description"
            :confirm-text="saveConfirmation.confirmText"
            :cancel-text="saveConfirmation.cancelText"
            :destructive="saveConfirmation.destructive"
            :details="saveConfirmation.details"
            @confirm="saveConfirmation.confirm"
            @cancel="saveConfirmation.cancel"
        />
    </AppLayout>
</template>
