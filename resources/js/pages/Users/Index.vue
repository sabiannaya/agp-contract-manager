<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Pencil, Search, Shield, User as UserIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable, type DataTableColumn } from '@/components/ui/data-table';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { Select } from '@/components/ui/select';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit, index } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import type { User } from '@/types/auth';
import type { PaginatedData, Role, UserFilters } from '@/types/models';

const props = defineProps<{
    users: PaginatedData<User>;
    roles: Role[];
    filters: UserFilters;
}>();

const { t } = useI18n();
const { canUpdate } = usePermission();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('nav.users'), href: index().url },
];

// Role filter options
const roleOptions = computed(() => [
    { value: '', label: t('common.all') },
    ...props.roles.map((r) => ({ value: r.id, label: r.name })),
]);

// Filter state
const searchInput = ref(props.filters.search ?? '');
const roleFilter = ref<string | number | null>(props.filters.role_id ?? null);

const debouncedSearch = useDebounceFn((value: string) => {
    router.get(
        index().url,
        { ...props.filters, search: value || undefined, page: 1 },
        { preserveState: true, preserveScroll: true },
    );
}, 300);

watch(searchInput, (value) => debouncedSearch(value));

watch(roleFilter, (value) => {
    router.get(
        index().url,
        { ...props.filters, role_id: value ? Number(value) : undefined, page: 1 },
        { preserveState: true, preserveScroll: true },
    );
});

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
const columns: DataTableColumn<User>[] = [
    { key: 'name', label: t('users.name'), sortable: true },
    { key: 'email', label: t('users.email'), sortable: true },
    { key: 'roles', label: t('users.roles') },
    { key: 'actions', label: '', class: 'w-[70px]' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('nav.users')" />

        <div class="flex flex-col gap-6 p-6">
            <div>
                <Heading :title="t('nav.users')" :description="t('users.description')" />
            </div>

            <!-- Filters -->
            <div class="relative z-10 flex items-center gap-4">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="searchInput"
                        :placeholder="t('common.search')"
                        class="pl-9"
                    />
                </div>

                <Select
                    v-model="roleFilter"
                    :options="roleOptions"
                    :placeholder="t('users.filter_role')"
                    class="w-48"
                    clearable
                />
            </div>

            <!-- Data Table -->
            <div class="overflow-x-auto">
                <DataTable
                :columns="columns"
                :data="(users.data as unknown as Record<string, unknown>[])"
                :sort-field="props.filters.sort"
                :sort-direction="props.filters.direction"
                :empty-text="t('common.no_data')"
                hoverable
                @sort="handleSort"
            >
                <template #cell-name="{ value }">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-full bg-muted">
                            <UserIcon class="size-4 text-muted-foreground" />
                        </div>
                        <span class="font-medium">{{ value }}</span>
                    </div>
                </template>

                <template #cell-roles="{ row }">
                    <div class="flex flex-wrap gap-1">
                        <Badge
                            v-for="role in (row as unknown as User).roles"
                            :key="role.id"
                            variant="secondary"
                            class="gap-1"
                        >
                            <Shield class="size-3" />
                            {{ role.name }}
                        </Badge>
                        <span
                            v-if="!(row as unknown as User).roles?.length"
                            class="text-muted-foreground text-sm"
                        >
                            {{ t('users.no_roles') }}
                        </span>
                    </div>
                </template>

                <template #cell-actions="{ row }">
                    <Link
                        v-if="canUpdate('users')"
                        :href="edit(row as unknown as User).url"
                    >
                        <Button variant="ghost" size="icon" class="size-8">
                            <Pencil class="size-4" />
                            <span class="sr-only">{{ t('common.edit') }}</span>
                        </Button>
                    </Link>
                </template>
                </DataTable>
            </div>

            <!-- Pagination -->
            <div class="shrink-0 border-t bg-background pt-4">
                <Pagination
                    :links="users.links"
                    :from="users.from"
                    :to="users.to"
                    :total="users.total"
                    @navigate="handleNavigate"
                />
            </div>
        </div>
    </AppLayout>
</template>
