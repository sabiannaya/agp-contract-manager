<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Download, Eye, FileText, Pencil, User } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { StatusBadge } from '@/components/ui/status-badge';
import { useDateFormat } from '@/composables/useDateFormat';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as showContract } from '@/routes/contracts';
import { download as downloadDocument, preview as previewDocument } from '@/routes/documents';
import { edit, index } from '@/routes/tickets';
import { show as showVendor } from '@/routes/vendors';
import type { BreadcrumbItem } from '@/types';
import type { Ticket } from '@/types/models';

const props = defineProps<{
    ticket: Ticket;
}>();

const { t } = useI18n();
const { formatDateOnly, formatDateTime } = useDateFormat();
const { canUpdate } = usePermission();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('nav.tickets'), href: index().url },
    { title: props.ticket.number, href: '#' },
];

function goBack() {
    router.get(index().url);
}

function editTicket() {
    router.get(edit(props.ticket.id).url);
}

function viewContract() {
    if (props.ticket.contract?.id) {
        router.get(showContract(props.ticket.contract.id).url);
    }
}

function viewVendor() {
    if (props.ticket.vendor?.id) {
        router.get(showVendor(props.ticket.vendor.id).url);
    }
}

function handleDownload(documentId: number) {
    window.open(downloadDocument(documentId).url, '_blank');
}

function handlePreview(documentId: number) {
    window.open(previewDocument(documentId).url, '_blank');
}

function getDocumentTypeLabel(type: string): string {
    return t(`documents.types.${type}`);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`${t('tickets.ticket')} - ${ticket.number}`" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="icon" @click="goBack">
                        <ArrowLeft class="size-4" />
                    </Button>
                    <Heading :title="ticket.number" :description="t('tickets.ticket_details')" />
                </div>
                <div class="flex items-center gap-2">
                    <StatusBadge
                        :active="ticket.status === 'complete'"
                        :active-text="t('tickets.status_types.complete')"
                        :inactive-text="t('tickets.status_types.incomplete')"
                    />
                    <Button v-if="canUpdate('tickets')" @click="editTicket">
                        <Pencil class="mr-2 size-4" />
                        {{ t('common.edit') }}
                    </Button>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Ticket Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="size-5" />
                            {{ t('tickets.ticket_info') }}
                        </CardTitle>
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
                            <div class="sm:col-span-2">
                                <p class="text-muted-foreground text-sm">{{ t('tickets.notes') }}</p>
                                <p class="font-medium">{{ ticket.notes || '-' }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Contract & Vendor Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('tickets.related_info') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Contract -->
                        <div
                            v-if="ticket.contract"
                            class="cursor-pointer rounded-lg border p-4 transition-colors hover:bg-muted/50"
                            @click="viewContract"
                        >
                            <p class="text-muted-foreground text-sm">{{ t('tickets.contract') }}</p>
                            <p class="font-medium">{{ ticket.contract.number }}</p>
                            <p class="text-muted-foreground text-sm">
                                <Calendar class="mr-1 inline size-3" />
                                {{ formatDateOnly(ticket.contract.date) }}
                            </p>
                        </div>

                        <!-- Vendor -->
                        <div
                            v-if="ticket.vendor"
                            class="cursor-pointer rounded-lg border p-4 transition-colors hover:bg-muted/50"
                            @click="viewVendor"
                        >
                            <p class="text-muted-foreground text-sm">{{ t('tickets.vendor') }}</p>
                            <p class="font-medium">{{ ticket.vendor.name }}</p>
                            <p class="text-muted-foreground text-sm">{{ ticket.vendor.code }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Documents -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="size-5" />
                        {{ t('documents.documents') }}
                        <Badge variant="secondary">{{ ticket.documents?.length || 0 }}</Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="ticket.documents?.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="document in ticket.documents"
                            :key="document.id"
                            class="rounded-lg border p-4"
                        >
                            <div class="mb-2 flex items-start justify-between">
                                <div class="flex-1">
                                    <Badge variant="outline" class="mb-2">
                                        {{ getDocumentTypeLabel(document.type) }}
                                    </Badge>
                                    <p class="text-sm font-medium">{{ document.original_name }}</p>
                                    <p class="text-muted-foreground text-xs">
                                        {{ (document.file_size / 1024).toFixed(1) }} KB
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="handlePreview(document.id)"
                                >
                                    <Eye class="mr-1 size-3" />
                                    {{ t('common.preview') }}
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="handleDownload(document.id)"
                                >
                                    <Download class="mr-1 size-3" />
                                    {{ t('common.download') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-muted-foreground py-8 text-center">
                        <FileText class="mx-auto mb-2 size-8 opacity-50" />
                        <p>{{ t('documents.no_documents') }}</p>
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
                            <p class="font-medium">{{ ticket.created_by?.name || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-sm">{{ t('common.created_at') }}</p>
                            <p class="font-medium">{{ formatDateTime(ticket.created_at) }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-sm">{{ t('common.updated_by') }}</p>
                            <p class="font-medium">{{ ticket.updated_by?.name || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-sm">{{ t('common.updated_at') }}</p>
                            <p class="font-medium">{{ formatDateTime(ticket.updated_at) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
