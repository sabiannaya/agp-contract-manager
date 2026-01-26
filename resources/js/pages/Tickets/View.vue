<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    ChevronDown,
    ChevronRight,
    Eye,
    FileText,
    Filter,
    Upload,
    X,
} from 'lucide-vue-next';
import { reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import DocumentUploadGrid from '@/components/DocumentUploadGrid.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
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
import { Pagination } from '@/components/ui/pagination';
import { StatusBadge } from '@/components/ui/status-badge';
import { useDateFormat } from '@/composables/useDateFormat';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy as destroyDocument, store as storeDocument } from '@/routes/documents';
import { view } from '@/routes/tickets';
import type { BreadcrumbItem } from '@/types';
import type {
    DocumentType,
    PaginatedData,
    Ticket,
    TicketViewFilters,
} from '@/types/models';
import { DOCUMENT_TYPES } from '@/types/models';

const props = defineProps<{
    tickets: PaginatedData<Ticket>;
    filters: TicketViewFilters;
}>();

const { t } = useI18n();
const { formatDateOnly } = useDateFormat();
const { canUpdate } = usePermission();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('nav.view_tickets'), href: view().url },
];

// Filter state
const filterInputs = reactive({
    vendor_name: props.filters.vendor_name ?? '',
    ticket_number: props.filters.ticket_number ?? '',
    date: props.filters.date ?? '',
    status: props.filters.status ?? '',
});

const debouncedFilter = useDebounceFn(() => {
    router.get(
        view().url,
        {
            vendor_name: filterInputs.vendor_name || undefined,
            ticket_number: filterInputs.ticket_number || undefined,
            date: filterInputs.date || undefined,
            status: filterInputs.status || undefined,
            page: 1,
        },
        { preserveState: true, preserveScroll: true },
    );
}, 300);

watch(filterInputs, debouncedFilter);

const showFilters = ref(false);

function clearFilters() {
    filterInputs.vendor_name = '';
    filterInputs.ticket_number = '';
    filterInputs.date = '';
    filterInputs.status = '';
    router.get(view().url, {}, { preserveState: true, preserveScroll: true });
}

function handleNavigate(url: string) {
    router.get(url, {}, { preserveState: true, preserveScroll: true });
}

// Expanded rows for document management
const expandedRows = ref<Set<number>>(new Set());

function toggleRow(ticketId: number) {
    if (expandedRows.value.has(ticketId)) {
        expandedRows.value.delete(ticketId);
    } else {
        expandedRows.value.add(ticketId);
    }
}

function getCompleteness(ticket: Ticket): { count: number; total: number } {
    const count = ticket.documents?.length ?? ticket.documents_count ?? 0;
    return { count, total: 5 };
}

// Document management modal
const isDocModalOpen = ref(false);
const selectedTicket = ref<Ticket | null>(null);
const documentFiles = ref<Record<DocumentType, File | null>>(
    Object.fromEntries(DOCUMENT_TYPES.map((t) => [t, null])) as Record<DocumentType, File | null>,
);
const uploadingDocs = ref(false);

function openDocModal(ticket: Ticket) {
    selectedTicket.value = ticket;
    // Reset file inputs
    documentFiles.value = Object.fromEntries(
        DOCUMENT_TYPES.map((t) => [t, null]),
    ) as Record<DocumentType, File | null>;
    isDocModalOpen.value = true;
}

async function handleDocumentUpload() {
    if (!selectedTicket.value) return;

    uploadingDocs.value = true;

    try {
        // Upload each file that has been selected
        for (const [type, file] of Object.entries(documentFiles.value)) {
            if (file) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('ticket_id', String(selectedTicket.value.id));
                formData.append('type', type);

                await fetch(storeDocument().url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>(
                            'meta[name="csrf-token"]',
                        )?.content ?? '',
                    },
                });
            }
        }

        // Refresh the page data
        router.reload({ only: ['tickets'] });
        isDocModalOpen.value = false;
    } finally {
        uploadingDocs.value = false;
    }
}

function handleDocumentRemove(type: DocumentType) {
    if (!selectedTicket.value) return;

    const existingDoc = selectedTicket.value.documents?.find((d) => d.type === type);
    if (existingDoc) {
        router.delete(destroyDocument(existingDoc.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['tickets'] });
            },
        });
    }
}

// Quick document status view
function getDocumentIcon(ticket: Ticket, type: DocumentType): 'exists' | 'missing' {
    const doc = ticket.documents?.find((d) => d.type === type);
    return doc ? 'exists' : 'missing';
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('nav.view_tickets')" />

        <div class="space-y-6">
            <Heading
                :title="t('nav.view_tickets')"
                :description="t('tickets.view_description')"
            />

            <!-- Filter toggle -->
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    @click="showFilters = !showFilters"
                >
                    <Filter class="mr-2 size-4" />
                    {{ t('common.filters') }}
                    <ChevronDown
                        :class="[
                            'ml-2 size-4 transition-transform',
                            showFilters && 'rotate-180',
                        ]"
                    />
                </Button>

                <Button
                    v-if="Object.values(filterInputs).some(Boolean)"
                    variant="ghost"
                    size="sm"
                    @click="clearFilters"
                >
                    <X class="mr-2 size-4" />
                    {{ t('common.clear_filters') }}
                </Button>
            </div>

            <!-- Filters panel -->
            <div
                v-show="showFilters"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-4 border rounded-lg bg-muted/20"
            >
                <div class="space-y-2">
                    <Label>{{ t('tickets.vendor') }}</Label>
                    <Input
                        v-model="filterInputs.vendor_name"
                        :placeholder="t('tickets.filter_vendor')"
                    />
                </div>

                <div class="space-y-2">
                    <Label>{{ t('tickets.number') }}</Label>
                    <Input
                        v-model="filterInputs.ticket_number"
                        :placeholder="t('tickets.filter_number')"
                    />
                </div>

                <div class="space-y-2">
                    <Label>{{ t('tickets.date') }}</Label>
                    <Input v-model="filterInputs.date" type="date" />
                </div>

                <div class="space-y-2">
                    <Label>{{ t('common.status') }}</Label>
                    <select
                        v-model="filterInputs.status"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        <option value="">{{ t('common.all') }}</option>
                        <option value="complete">{{ t('tickets.status.complete') }}</option>
                        <option value="incomplete">{{ t('tickets.status.incomplete') }}</option>
                    </select>
                </div>
            </div>

            <!-- Data Table with expandable rows -->
            <div class="rounded-md border">
                <table class="w-full caption-bottom text-sm">
                    <thead class="[&_tr]:border-b bg-muted/50">
                        <tr class="border-b">
                            <th class="h-10 w-[40px] px-2"></th>
                            <th class="h-10 px-4 text-left font-medium text-muted-foreground">
                                {{ t('tickets.number') }}
                            </th>
                            <th class="h-10 px-4 text-left font-medium text-muted-foreground">
                                {{ t('tickets.date') }}
                            </th>
                            <th class="h-10 px-4 text-left font-medium text-muted-foreground">
                                {{ t('tickets.vendor') }}
                            </th>
                            <th class="h-10 px-4 text-left font-medium text-muted-foreground">
                                {{ t('tickets.contract') }}
                            </th>
                            <th class="h-10 px-4 text-left font-medium text-muted-foreground">
                                {{ t('tickets.completeness') }}
                            </th>
                            <th class="h-10 px-4 text-left font-medium text-muted-foreground">
                                {{ t('common.status') }}
                            </th>
                            <th class="h-10 w-[100px] px-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="tickets.data.length === 0">
                            <tr>
                                <td
                                    colspan="8"
                                    class="h-24 text-center text-muted-foreground"
                                >
                                    {{ t('common.no_data') }}
                                </td>
                            </tr>
                        </template>

                        <template v-for="ticket in tickets.data" :key="ticket.id">
                            <!-- Main row -->
                            <tr class="border-b hover:bg-muted/50 transition-colors">
                                <td class="px-2">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-8"
                                        @click="toggleRow(ticket.id)"
                                    >
                                        <ChevronRight
                                            :class="[
                                                'size-4 transition-transform',
                                                expandedRows.has(ticket.id) && 'rotate-90',
                                            ]"
                                        />
                                    </Button>
                                </td>
                                <td class="px-4 py-3 font-medium">
                                    {{ ticket.number }}
                                </td>
                                <td class="px-4 py-3">{{ formatDateOnly(ticket.date) }}</td>
                                <td class="px-4 py-3">{{ ticket.vendor?.name }}</td>
                                <td class="px-4 py-3">{{ ticket.contract?.number }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'text-sm font-medium',
                                            getCompleteness(ticket).count === 5
                                                ? 'text-green-600'
                                                : 'text-amber-600',
                                        ]"
                                    >
                                        {{ getCompleteness(ticket).count }}/5
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge :status="ticket.status" />
                                </td>
                                <td class="px-4 py-3">
                                    <Button
                                        v-if="canUpdate('tickets')"
                                        variant="outline"
                                        size="sm"
                                        @click="openDocModal(ticket)"
                                    >
                                        <Upload class="mr-2 size-4" />
                                        {{ t('documents.manage') }}
                                    </Button>
                                </td>
                            </tr>

                            <!-- Expanded row with document details -->
                            <tr v-if="expandedRows.has(ticket.id)">
                                <td colspan="8" class="p-4 bg-muted/20">
                                    <div class="grid grid-cols-5 gap-4">
                                        <div
                                            v-for="type in DOCUMENT_TYPES"
                                            :key="type"
                                            :class="[
                                                'flex items-center gap-2 p-3 rounded-md border',
                                                getDocumentIcon(ticket, type) === 'exists'
                                                    ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950'
                                                    : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950',
                                            ]"
                                        >
                                            <FileText
                                                :class="[
                                                    'size-5',
                                                    getDocumentIcon(ticket, type) === 'exists'
                                                        ? 'text-green-600'
                                                        : 'text-amber-600',
                                                ]"
                                            />
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-medium truncate">
                                                    {{ t(`documents.${type}`) }}
                                                </p>
                                                <p class="text-xs text-muted-foreground truncate">
                                                    {{
                                                        ticket.documents?.find((d) => d.type === type)
                                                            ?.original_name ?? t('documents.noFile')
                                                    }}
                                                </p>
                                            </div>
                                            <a
                                                v-if="ticket.documents?.find((d) => d.type === type)"
                                                :href="`/documents/${ticket.documents.find((d) => d.type === type)?.id}/preview`"
                                                target="_blank"
                                                class="p-1 hover:bg-background rounded"
                                            >
                                                <Eye class="size-4 text-muted-foreground hover:text-foreground" />
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination
                :links="tickets.links"
                :from="tickets.from"
                :to="tickets.to"
                :total="tickets.total"
                @navigate="handleNavigate"
            />
        </div>

        <!-- Document Upload Modal -->
        <Dialog v-model:open="isDocModalOpen">
            <DialogContent class="sm:max-w-[700px]">
                <DialogHeader>
                    <DialogTitle>
                        {{ t('documents.manage') }} - {{ selectedTicket?.number }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ t('documents.manage_description') }}
                    </DialogDescription>
                </DialogHeader>

                <DocumentUploadGrid
                    v-if="selectedTicket"
                    :documents="selectedTicket.documents ?? []"
                    v-model:files="documentFiles"
                    :disabled="uploadingDocs"
                    @remove="handleDocumentRemove"
                />

                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="isDocModalOpen = false"
                        :disabled="uploadingDocs"
                    >
                        {{ t('common.cancel') }}
                    </Button>
                    <Button
                        @click="handleDocumentUpload"
                        :disabled="uploadingDocs || !Object.values(documentFiles).some(Boolean)"
                    >
                        {{ uploadingDocs ? t('common.uploading') : t('common.upload') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
