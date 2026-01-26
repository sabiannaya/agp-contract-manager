<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Eye, FileText, Save, Trash2, Upload } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { FileUpload } from '@/components/ui/file-upload';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { StatusBadge } from '@/components/ui/status-badge';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy as destroyDocument, preview as previewDocument, store as storeDocument } from '@/routes/documents';
import { index, show, update } from '@/routes/tickets';
import type { BreadcrumbItem } from '@/types';
import type {
    ContractOption,
    Document,
    DocumentType,
    Ticket,
    TicketForm,
    VendorOption,
} from '@/types/models';
import { DOCUMENT_TYPES } from '@/types/models';

const props = defineProps<{
    ticket: Ticket;
    vendors: VendorOption[];
    contracts: ContractOption[];
}>();

const { t } = useI18n();
const toast = useToast();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.tickets'), href: index().url },
    { title: props.ticket.number, href: show(props.ticket.id).url },
    { title: t('common.edit'), href: '#' },
]);

// Filter contracts by vendor
const filteredContracts = computed(() => {
    if (!form.vendor_id) return props.contracts;
    return props.contracts.filter((c) => c.vendor_id === form.vendor_id);
});

// Select options
const vendorOptions = computed(() =>
    props.vendors.map((v) => ({ value: v.id, label: `${v.code} - ${v.name}` })),
);

const contractOptions = computed(() =>
    filteredContracts.value.map((c) => ({ value: c.id, label: c.number })),
);

// Form state
const form = useForm<TicketForm>({
    date: props.ticket.date,
    contract_id: props.ticket.contract_id,
    vendor_id: props.ticket.vendor_id,
    notes: props.ticket.notes ?? '',
    is_active: props.ticket.is_active,
});

// When vendor changes, reset contract if it doesn't belong to the vendor
watch(() => form.vendor_id, (vendorId) => {
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
    router.get(show(props.ticket.id).url);
}

function submitForm() {
    form.put(update(props.ticket.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('tickets.updated_successfully'));
            router.visit(show(props.ticket.id).url);
        },
        onError: () => {
            toast.error(t('tickets.update_failed'));
        },
    });
}

// Document management
const documentFiles = ref<Record<DocumentType, File | null>>(
    Object.fromEntries(DOCUMENT_TYPES.map((t) => [t, null])) as Record<DocumentType, File | null>,
);
const uploadingDoc = ref<DocumentType | null>(null);
const deletingDoc = ref<number | null>(null);
const deleteConfirmOpen = ref(false);
const docToDelete = ref<Document | null>(null);

// Get current documents from ticket
const currentDocuments = computed(() => props.ticket.documents ?? []);

// Calculate completeness
const completeness = computed(() => {
    const count = DOCUMENT_TYPES.filter((type) => {
        return currentDocuments.value.some((d) => d.type === type);
    }).length;
    return {
        count,
        total: DOCUMENT_TYPES.length,
        isComplete: count >= DOCUMENT_TYPES.length,
    };
});

function getExistingDocument(type: DocumentType): Document | undefined {
    return currentDocuments.value.find((d) => d.type === type);
}

async function handleFileUpload(type: DocumentType, file: File | null) {
    if (!file) return;

    uploadingDoc.value = type;

    try {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('ticket_id', String(props.ticket.id));
        formData.append('type', type);

        const response = await fetch(storeDocument().url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>(
                    'meta[name="csrf-token"]',
                )?.content ?? '',
            },
        });

        if (response.ok) {
            toast.success(t('documents.uploaded_successfully'));
            // Refresh to get updated documents
            router.reload({ only: ['ticket'] });
        } else {
            const data = await response.json();
            toast.error(data.message || t('documents.upload_failed'));
        }
    } catch {
        toast.error(t('documents.upload_failed'));
    } finally {
        uploadingDoc.value = null;
        documentFiles.value[type] = null;
    }
}

function openDeleteConfirm(doc: Document) {
    docToDelete.value = doc;
    deleteConfirmOpen.value = true;
}

function confirmDeleteDocument() {
    if (!docToDelete.value) return;

    deletingDoc.value = docToDelete.value.id;

    router.delete(destroyDocument(docToDelete.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('documents.deleted_successfully'));
            deleteConfirmOpen.value = false;
            docToDelete.value = null;
            deletingDoc.value = null;
        },
        onError: () => {
            toast.error(t('documents.delete_failed'));
        },
        onFinish: () => {
            deletingDoc.value = null;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`${t('common.edit')} - ${ticket.number}`" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" @click="goBack">
                    <ArrowLeft class="size-4" />
                </Button>
                <Heading
                    :title="`${t('tickets.edit')} - ${ticket.number}`"
                    :description="t('tickets.edit_description')"
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
                            {{ t('tickets.edit_info_description') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label>{{ t('tickets.number') }}</Label>
                                <Input :model-value="ticket.number" disabled />
                                <p class="text-xs text-muted-foreground">
                                    {{ t('tickets.number_readonly') }}
                                </p>
                            </div>

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
                                    :options="contractOptions"
                                    :placeholder="t('tickets.select_contract')"
                                    :disabled="form.processing"
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

                <!-- Documents Card -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Upload class="size-5" />
                                    {{ t('documents.title') }}
                                </CardTitle>
                                <CardDescription>
                                    {{ t('documents.manage_description') }}
                                </CardDescription>
                            </div>
                            <StatusBadge :variant="completeness.isComplete ? 'complete' : 'incomplete'">
                                {{ completeness.count }}/{{ completeness.total }}
                            </StatusBadge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="type in DOCUMENT_TYPES"
                                :key="type"
                                class="rounded-lg border p-4 space-y-3"
                            >
                                <div class="flex items-center justify-between">
                                    <Label class="font-medium">{{ t(`documents.${type}`) }}</Label>
                                    <Badge
                                        :variant="getExistingDocument(type) ? 'default' : 'outline'"
                                        class="text-xs"
                                    >
                                        {{ getExistingDocument(type) ? t('status.complete') : t('status.incomplete') }}
                                    </Badge>
                                </div>

                                <!-- Existing document -->
                                <div v-if="getExistingDocument(type)" class="space-y-3">
                                    <div class="rounded-md bg-muted/50 dark:bg-muted/20 p-3 border border-border/50 dark:border-border/30">
                                        <div class="flex items-start gap-2">
                                            <FileText class="size-4 text-primary dark:text-primary/90 mt-0.5 shrink-0" />
                                            <span class="flex-1 text-sm font-medium text-foreground dark:text-foreground/90 break-all leading-relaxed">
                                                {{ getExistingDocument(type)!.original_name }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <a
                                            :href="previewDocument(getExistingDocument(type)!.id).url"
                                            target="_blank"
                                            class="inline-flex items-center justify-center gap-1.5 flex-1 h-8 text-xs rounded-md border border-input bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 transition-colors px-3"
                                        >
                                            <Eye class="size-3.5" />
                                            {{ t('common.preview') }}
                                        </a>
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="sm"
                                            class="h-8 px-3"
                                            :disabled="deletingDoc === getExistingDocument(type)!.id"
                                            @click="openDeleteConfirm(getExistingDocument(type)!)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Upload new document -->
                                <div v-else class="space-y-2">
                                    <FileUpload
                                        :model-value="documentFiles[type]"
                                        :disabled="uploadingDoc === type"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        :max-size="10240"
                                        @update:model-value="handleFileUpload(type, $event)"
                                    />
                                    <div v-if="uploadingDoc === type" class="flex items-center gap-2 text-xs text-primary dark:text-primary/90 animate-pulse">
                                        <Upload class="size-3.5" />
                                        {{ t('common.uploading') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="mt-4 text-xs text-muted-foreground">
                            {{ t('documents.fileTypes') }}
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
                        <Save class="mr-2 size-4" />
                        {{ form.processing ? t('common.saving') : t('common.save') }}
                    </Button>
                </div>
            </form>
        </div>

        <!-- Delete Document Confirmation -->
        <Dialog v-model:open="deleteConfirmOpen">
            <DialogContent class="sm:max-w-100">
                <DialogHeader>
                    <DialogTitle>{{ t('common.confirm_delete') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('documents.delete_confirmation', { name: docToDelete?.original_name }) }}
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="deleteConfirmOpen = false"
                        :disabled="deletingDoc !== null"
                    >
                        {{ t('common.cancel') }}
                    </Button>
                    <Button
                        variant="destructive"
                        @click="confirmDeleteDocument"
                        :disabled="deletingDoc !== null"
                    >
                        <Trash2 class="mr-2 size-4" />
                        {{ t('common.delete') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
