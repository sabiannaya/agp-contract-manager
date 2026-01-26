<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FileText, Plus } from 'lucide-vue-next';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
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

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.tickets'), href: index().url },
    { title: t('tickets.create'), href: '#' },
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
    date: new Date().toISOString().split('T')[0],
    contract_id: null,
    vendor_id: null,
    notes: '',
    is_active: true,
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
    router.get(index().url);
}

function submitForm() {
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
    </AppLayout>
</template>
