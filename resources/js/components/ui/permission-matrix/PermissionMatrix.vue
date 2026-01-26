<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { cn } from '@/lib/utils';
import { Checkbox } from '@/components/ui/checkbox';
import type { Permission, PermissionAction, PermissionResource } from '@/types';

const props = defineProps<{
    permissions: Permission[];
    selectedIds: number[];
    resources: PermissionResource[];
    actions: PermissionAction[];
    disabled?: boolean;
    class?: HTMLAttributes['class'];
}>();

const emits = defineEmits<{
    (e: 'update:selectedIds', ids: number[]): void;
}>();

const { t } = useI18n();

// Create a map for quick lookup
const permissionMap = computed(() => {
    const map: Record<string, Permission> = {};
    props.permissions.forEach((p) => {
        map[`${p.resource}.${p.action}`] = p;
    });
    return map;
});

function getPermission(resource: string, action: string): Permission | undefined {
    return permissionMap.value[`${resource}.${action}`];
}

function isSelected(permission: Permission | undefined): boolean {
    if (!permission) return false;
    return props.selectedIds.includes(permission.id);
}

function togglePermission(permission: Permission | undefined) {
    if (!permission || props.disabled) return;

    const newIds = [...props.selectedIds];
    const index = newIds.indexOf(permission.id);

    if (index === -1) {
        newIds.push(permission.id);
    } else {
        newIds.splice(index, 1);
    }

    emits('update:selectedIds', newIds);
}

function toggleResourceRow(resource: string) {
    if (props.disabled) return;

    const resourcePermissions = props.actions
        .map((action) => getPermission(resource, action))
        .filter((p): p is Permission => !!p);

    const allSelected = resourcePermissions.every((p) => props.selectedIds.includes(p.id));

    const newIds = [...props.selectedIds];

    if (allSelected) {
        // Deselect all
        resourcePermissions.forEach((p) => {
            const index = newIds.indexOf(p.id);
            if (index !== -1) newIds.splice(index, 1);
        });
    } else {
        // Select all
        resourcePermissions.forEach((p) => {
            if (!newIds.includes(p.id)) newIds.push(p.id);
        });
    }

    emits('update:selectedIds', newIds);
}

function toggleActionColumn(action: string) {
    if (props.disabled) return;

    const actionPermissions = props.resources
        .map((resource) => getPermission(resource, action))
        .filter((p): p is Permission => !!p);

    const allSelected = actionPermissions.every((p) => props.selectedIds.includes(p.id));

    const newIds = [...props.selectedIds];

    if (allSelected) {
        actionPermissions.forEach((p) => {
            const index = newIds.indexOf(p.id);
            if (index !== -1) newIds.splice(index, 1);
        });
    } else {
        actionPermissions.forEach((p) => {
            if (!newIds.includes(p.id)) newIds.push(p.id);
        });
    }

    emits('update:selectedIds', newIds);
}

function isResourceRowSelected(resource: string): boolean | 'indeterminate' {
    const resourcePermissions = props.actions
        .map((action) => getPermission(resource, action))
        .filter((p): p is Permission => !!p);

    const selectedCount = resourcePermissions.filter((p) => props.selectedIds.includes(p.id)).length;

    if (selectedCount === 0) return false;
    if (selectedCount === resourcePermissions.length) return true;
    return 'indeterminate';
}

function isActionColumnSelected(action: string): boolean | 'indeterminate' {
    const actionPermissions = props.resources
        .map((resource) => getPermission(resource, action))
        .filter((p): p is Permission => !!p);

    const selectedCount = actionPermissions.filter((p) => props.selectedIds.includes(p.id)).length;

    if (selectedCount === 0) return false;
    if (selectedCount === actionPermissions.length) return true;
    return 'indeterminate';
}
</script>

<template>
    <div :class="cn('overflow-auto rounded-md border', props.class)">
        <table class="w-full text-sm">
            <thead class="bg-muted/50">
                <tr class="border-b">
                    <th class="h-10 px-4 text-left font-medium text-muted-foreground">
                        {{ t('permissions.resource') }}
                    </th>
                    <th
                        v-for="action in actions"
                        :key="action"
                        class="h-10 px-4 text-center font-medium text-muted-foreground min-w-[100px]"
                    >
                        <div class="flex flex-col items-center gap-1">
                            <span>{{ t(`permissions.${action}`) }}</span>
                            <Checkbox
                                :checked="isActionColumnSelected(action)"
                                :disabled="disabled"
                                @update:checked="toggleActionColumn(action)"
                            />
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="resource in resources"
                    :key="resource"
                    class="border-b last:border-0 hover:bg-muted/30"
                >
                    <td class="p-4 font-medium">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                :checked="isResourceRowSelected(resource)"
                                :disabled="disabled"
                                @update:checked="toggleResourceRow(resource)"
                            />
                            <span>{{ t(`permissions.${resource}`) }}</span>
                        </div>
                    </td>
                    <td
                        v-for="action in actions"
                        :key="action"
                        class="p-4 text-center"
                    >
                        <Checkbox
                            :checked="isSelected(getPermission(resource, action))"
                            :disabled="disabled || !getPermission(resource, action)"
                            @update:checked="togglePermission(getPermission(resource, action))"
                        />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
