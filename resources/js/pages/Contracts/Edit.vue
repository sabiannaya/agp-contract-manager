<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ActionConfirmationDialog from '@/components/ActionConfirmationDialog.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { useActionConfirmation } from '@/composables/useActionConfirmation';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, update } from '@/routes/contracts';
import { list as vendorList } from '@/routes/vendors';
import type { BreadcrumbItem } from '@/types';
import type { Contract, ContractForm, VendorOption } from '@/types/models';

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
const saveConfirmation = useActionConfirmation();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('nav.contracts'), href: index().url },
    { title: props.contract.number, href: show(props.contract.id).url },
    { title: t('common.edit'), href: '#' },
];

const vendors = ref<VendorOption[]>([]);

async function loadVendors() {
    const response = await fetch(vendorList().url);
    vendors.value = await response.json();
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

const form = useForm<ContractForm & { assigned_master_user_id: number | null }>({
    number: props.contract.number,
    date: String(props.contract.date).split('T')[0],
    vendor_id: props.contract.vendor_id,
    amount: Number(props.contract.amount ?? 0),
    cooperation_type: props.contract.cooperation_type,
    term_count: props.contract.term_count,
    term_percentages: (props.contract.term_percentages ?? []).map((value) => Number(value)),
    is_active: props.contract.is_active,
    assigned_master_user_id: (props.contract as any).assigned_master_user_id ?? null,
});

const termInputs = ref<number[]>((props.contract.term_percentages ?? []).map((value) => Number(value)));

watch(() => form.term_count, (count) => {
    if (count && count > 0) {
        const current = [...termInputs.value];
        if (count > current.length) {
            for (let i = current.length; i < count; i++) current.push(0);
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

function formatCurrency(amount: number | undefined | null): string {
    if (amount == null) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

function executeSubmit() {
    form.put(update(props.contract.id).url);
}

function submitForm() {
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
        executeSubmit,
    );
}

function cancel() {
    router.get(show(props.contract.id).url);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`${t('common.edit')} - ${props.contract.number}`" />

        <div class="flex flex-col gap-6 p-6">
            <Heading :title="t('contracts.edit')" :description="t('contracts.edit_description')" />

            <div class="rounded-lg border bg-card p-6">
                <form @submit.prevent="submitForm" class="space-y-4">
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

                        <div class="space-y-2">
                            <Label>{{ t('contracts.assigned_master') }}</Label>
                            <Select v-model="form.assigned_master_user_id" :options="masterOptions" :placeholder="t('contracts.select_master')" :disabled="form.processing" clearable />
                            <p class="text-xs text-muted-foreground">{{ t('contracts.master_hint') }}</p>
                            <p v-if="form.errors.assigned_master_user_id" class="text-sm text-destructive">{{ form.errors.assigned_master_user_id }}</p>
                        </div>

                        <div class="rounded-lg border bg-muted/30 p-3 text-xs text-muted-foreground">
                            {{ t('contracts.approvers') }}: manage from contract detail page.
                        </div>

                        <div v-if="form.cooperation_type === 'progress'" class="space-y-4 rounded-lg border p-4">
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

                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="outline" @click="cancel" :disabled="form.processing">{{ t('common.cancel') }}</Button>
                        <Button type="submit" :disabled="form.processing">{{ form.processing ? t('common.saving') : t('common.save') }}</Button>
                    </div>
                </form>
            </div>
        </div>

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
    </AppLayout>
</template>
