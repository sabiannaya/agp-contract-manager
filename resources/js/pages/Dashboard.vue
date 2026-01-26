<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    Building2,
    FileText,
    Receipt,
    TrendingUp,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { StatusBadge } from '@/components/ui/status-badge';
import { useDateFormat } from '@/composables/useDateFormat';

const { t } = useI18n();
const { formatDateOnly } = useDateFormat();

interface DashboardStats {
    vendors: { total: number; active: number };
    contracts: { total: number; active: number; total_amount: number };
    tickets: { total: number; complete: number; incomplete: number };
    documents: { total: number };
}

interface RecentTicket {
    id: number;
    number: string;
    date: string;
    status: 'complete' | 'incomplete';
    contract_number: string | null;
    vendor_name: string | null;
}

interface ContractByType {
    count: number;
    total_amount: number;
}

const props = defineProps<{
    stats: DashboardStats;
    recentTickets: RecentTicket[];
    contractsByType: Record<string, ContractByType>;
    ticketsByStatus: Record<string, number>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

function formatCurrency(amount: number | undefined | null): string {
    if (amount == null) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

function formatNumber(num: number): string {
    return new Intl.NumberFormat('id-ID').format(num);
}

const statCards = computed(() => [
    {
        title: t('nav.vendors'),
        value: props.stats.vendors.total,
        description: `${props.stats.vendors.active} ${t('common.active').toLowerCase()}`,
        icon: Building2,
        color: 'text-blue-500',
        bgColor: 'bg-blue-500/10',
    },
    {
        title: t('nav.contracts'),
        value: props.stats.contracts.total,
        description: `${props.stats.contracts.active} ${t('common.active').toLowerCase()}`,
        icon: FileText,
        color: 'text-green-500',
        bgColor: 'bg-green-500/10',
    },
    {
        title: t('nav.tickets'),
        value: props.stats.tickets.total,
        description: `${props.stats.tickets.incomplete} ${t('tickets.status_types.incomplete').toLowerCase()}`,
        icon: Receipt,
        color: 'text-orange-500',
        bgColor: 'bg-orange-500/10',
    },
    {
        title: t('dashboard.total_contract_value'),
        value: formatCurrency(props.stats.contracts.total_amount),
        description: t('dashboard.active_contracts_only'),
        icon: TrendingUp,
        color: 'text-purple-500',
        bgColor: 'bg-purple-500/10',
        isAmount: true,
    },
]);

const ticketStatusColors: Record<string, string> = {
    complete: 'bg-green-500',
    incomplete: 'bg-yellow-500',
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="(stat, index) in statCards" :key="index">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ stat.title }}</CardTitle>
                        <div :class="[stat.bgColor, 'rounded-full p-2']">
                            <component :is="stat.icon" :class="['size-4', stat.color]" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stat.isAmount ? stat.value : formatNumber(stat.value as number) }}
                        </div>
                        <p class="text-xs text-muted-foreground">{{ stat.description }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Main Content -->
            <div class="grid gap-4 lg:grid-cols-7">
                <!-- Recent Tickets -->
                <Card class="lg:col-span-4">
                    <CardHeader>
                        <CardTitle>{{ t('dashboard.recent_tickets') }}</CardTitle>
                        <CardDescription>{{ t('dashboard.latest_payment_requests') }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-for="ticket in recentTickets"
                                :key="ticket.id"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div class="space-y-1">
                                    <p class="text-sm font-medium leading-none">{{ ticket.number }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ ticket.vendor_name }} - {{ ticket.contract_number }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-muted-foreground">{{ formatDateOnly(ticket.date) }}</span>
                                    <StatusBadge :status="ticket.status" />
                                </div>
                            </div>
                            <div v-if="recentTickets.length === 0" class="py-8 text-center text-muted-foreground">
                                {{ t('common.no_data') }}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Sidebar Stats -->
                <div class="lg:col-span-3 space-y-4">
                    <!-- Contracts by Type -->
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ t('dashboard.contracts_by_type') }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div
                                    v-for="(data, type) in contractsByType"
                                    :key="type"
                                    class="flex items-center justify-between"
                                >
                                    <div class="flex items-center gap-2">
                                        <Badge :variant="type === 'progress' ? 'default' : 'secondary'">
                                            {{ t(`contracts.types.${type}`) }}
                                        </Badge>
                                        <span class="text-sm text-muted-foreground">
                                            {{ data.count }} {{ t('contracts.contracts').toLowerCase() }}
                                        </span>
                                    </div>
                                    <span class="text-sm font-medium">
                                        {{ formatCurrency(data.total_amount) }}
                                    </span>
                                </div>
                                <div v-if="Object.keys(contractsByType).length === 0" class="py-4 text-center text-muted-foreground">
                                    {{ t('common.no_data') }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Tickets by Status -->
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ t('dashboard.tickets_by_status') }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div
                                    v-for="(count, status) in ticketsByStatus"
                                    :key="status"
                                    class="flex items-center justify-between"
                                >
                                    <div class="flex items-center gap-2">
                                        <div :class="['size-2 rounded-full', ticketStatusColors[status] || 'bg-gray-500']" />
                                        <span class="text-sm">{{ t(`tickets.status_types.${status}`) }}</span>
                                    </div>
                                    <span class="text-sm font-medium">{{ formatNumber(count) }}</span>
                                </div>
                                <div v-if="Object.keys(ticketsByStatus).length === 0" class="py-4 text-center text-muted-foreground">
                                    {{ t('common.no_data') }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Stats -->
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ t('dashboard.documents') }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">{{ t('dashboard.total_documents') }}</span>
                                <span class="text-2xl font-bold">{{ formatNumber(stats.documents.total) }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
