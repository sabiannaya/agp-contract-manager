<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, FileText, Pencil, User } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { StatusBadge } from '@/components/ui/status-badge';
import { useDateFormat } from '@/composables/useDateFormat';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit, index } from '@/routes/contracts';
import { show as showTicket } from '@/routes/tickets';
import type { BreadcrumbItem } from '@/types';
import type { Contract } from '@/types/models';

const props = defineProps<{
    contract: Contract;
}>();

const { t } = useI18n();
const { formatDateOnly, formatDateTime } = useDateFormat();
const { canUpdate } = usePermission();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('nav.contracts'), href: index().url },
    { title: props.contract.number, href: '#' },
];

function goBack() {
    router.get(index().url);
}

function editContract() {
    router.get(edit(props.contract.id).url);
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
                    <Button v-if="canUpdate('contracts')" @click="editContract">
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
        </div>
    </AppLayout>
</template>
