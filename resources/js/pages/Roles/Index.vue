<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { MoreHorizontal, Pencil, Plus, Search, Shield, Trash2, Users } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ActionConfirmationDialog from '@/components/ActionConfirmationDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable, type DataTableColumn } from '@/components/ui/data-table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { StatusBadge } from '@/components/ui/status-badge';
import { useActionConfirmation } from '@/composables/useActionConfirmation';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, destroy as destroyRoute, edit, index } from '@/routes/roles';
import type { BreadcrumbItem } from '@/types';
import type { PaginatedData, Role, RoleFilters } from '@/types/models';

const props = defineProps<{
    roles: PaginatedData<Role>;
    filters: RoleFilters;
}>();

const { t } = useI18n();
const { canCreate, canUpdate, canDelete } = usePermission();
const deleteConfirmation = useActionConfirmation();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('nav.roles'), href: index().url },
];

// Filter state
const searchInput = ref(props.filters.search ?? '');

const debouncedSearch = useDebounceFn((value: string) => {
    router.get(
        index().url,
        { ...props.filters, search: value || undefined, page: 1 },
        { preserveState: true, preserveScroll: true },
    );
}, 300);

watch(searchInput, (value) => debouncedSearch(value));

function handleSort(field: string, direction: 'asc' | 'desc') {
    router.get(
        index().url,
        { ...props.filters, sort: field, direction },
        { preserveState: true, preserveScroll: true },
    );
}

function handleNavigate(url: string) {
    router.get(url, {}, { preserveState: true, preserveScroll: true });
}

// Table columns
const columns: DataTableColumn<Role>[] = [
    { key: 'name', label: t('roles.name'), sortable: true },
    { key: 'description', label: t('roles.description') },
    { key: 'permissions_count', label: t('roles.permissions'), class: 'text-center' },
    { key: 'users_count', label: t('roles.users'), class: 'text-center' },
    { key: 'is_active', label: t('common.status'), sortable: true },
    { key: 'actions', label: '', class: 'w-[70px]' },
];

// Delete modal state
const deletingRole = ref<Role | null>(null);

function openDeleteModal(role: Role) {
    deletingRole.value = role;
    deleteConfirmation.requestConfirmation(
        {
            title: t('common.confirm_delete'),
            description: t('roles.delete_confirmation', { name: role.name }),
            confirmText: t('common.delete'),
            destructive: true,
            details: [
                { label: t('roles.name'), value: role.name },
            ],
        },
        confirmDelete,
    );
}

function confirmDelete() {
    if (!deletingRole.value) return;

    router.delete(destroyRoute(deletingRole.value.id).url, {
        onSuccess: () => {
            deletingRole.value = null;
        },
    });
}

function isAdminRole(role: Role): boolean {
    return role.name.toLowerCase() === 'admin';
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('nav.roles')" />

        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <Heading :title="t('nav.roles')" :description="t('roles.description')" />

                <Link v-if="canCreate('role_groups')" :href="create().url">
                    <Button>
                        <Plus class="mr-2 size-4" />
                        {{ t('roles.create') }}
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-4">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="searchInput"
                        :placeholder="t('common.search')"
                        class="pl-9"
                    />
                </div>
            </div>

            <!-- Data Table -->
            <div class="overflow-auto">
                <DataTable
                    :columns="columns"
                    :data="(roles.data as unknown as Record<string, unknown>[])"
                    :sort-field="props.filters.sort"
                    :sort-direction="props.filters.direction"
                    :empty-text="t('common.no_data')"
                    hoverable
                    @sort="handleSort"
                >
                <template #cell-name="{ row, value }">
                    <div class="flex items-center gap-2">
                        <Shield class="size-4 text-muted-foreground" />
                        <span class="font-medium">{{ value }}</span>
                        <Badge v-if="isAdminRole(row as unknown as Role)" variant="outline">
                            {{ t('roles.system') }}
                        </Badge>
                    </div>
                </template>

                <template #cell-permissions_count="{ value }">
                    <Badge variant="secondary">{{ value }}</Badge>
                </template>

                <template #cell-users_count="{ value }">
                    <div class="flex items-center justify-center gap-1">
                        <Users class="size-4 text-muted-foreground" />
                        <span>{{ value }}</span>
                    </div>
                </template>

                <template #cell-is_active="{ value }">
                    <StatusBadge :status="value ? 'active' : 'inactive'" />
                </template>

                <template #cell-actions="{ row }">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="icon" class="size-8">
                                <MoreHorizontal class="size-4" />
                                <span class="sr-only">{{ t('common.actions') }}</span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem
                                v-if="canUpdate('role_groups')"
                                as-child
                            >
                                <Link :href="edit(row as unknown as Role).url" class="flex items-center">
                                    <Pencil class="mr-2 size-4" />
                                    {{ t('common.edit') }}
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="canDelete('role_groups') && !isAdminRole(row as unknown as Role)"
                                class="text-destructive focus:text-destructive"
                                @click="openDeleteModal(row as unknown as Role)"
                            >
                                <Trash2 class="mr-2 size-4" />
                                {{ t('common.delete') }}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>
                </DataTable>
            </div>

            <!-- Pagination -->
            <div class="shrink-0 border-t bg-background pt-4">
                <Pagination
                    :links="roles.links"
                    :from="roles.from"
                    :to="roles.to"
                    :total="roles.total"
                    @navigate="handleNavigate"
                />
            </div>
        </div>

        <ActionConfirmationDialog
            v-model:open="deleteConfirmation.open"
            :title="deleteConfirmation.title"
            :description="deleteConfirmation.description"
            :confirm-text="deleteConfirmation.confirmText"
            :cancel-text="deleteConfirmation.cancelText"
            :destructive="deleteConfirmation.destructive"
            :details="deleteConfirmation.details"
            @confirm="deleteConfirmation.confirm"
            @cancel="deleteConfirmation.cancel"
        />
    </AppLayout>
</template>
