import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { AppPageProps, PermissionsMap, PermissionResource, PermissionAction } from '@/types';

/**
 * Composable for checking user permissions based on page-based role groups.
 * 
 * The permission system uses pages (modules) instead of resources:
 * - dashboard, vendors, contracts, tickets, role_groups, users
 * 
 * Each page has 4 possible actions: read, create, update, delete
 */
export function usePermission() {
    const page = usePage<AppPageProps>();

    const permissions = computed<PermissionsMap>(() => {
        return page.props.auth?.permissions ?? {};
    });

    /**
     * Check if user has a specific permission
     * @param pageSlug - The page/module slug (e.g., 'vendors', 'contracts')
     * @param action - The action (read, create, update, delete)
     */
    function can(pageSlug: PermissionResource, action: PermissionAction): boolean {
        const key = `${pageSlug}.${action}`;
        return permissions.value[key] === true;
    }

    /**
     * Check if user can read a page
     */
    function canRead(pageSlug: PermissionResource): boolean {
        return can(pageSlug, 'read');
    }

    /**
     * Check if user can create in a page
     */
    function canCreate(pageSlug: PermissionResource): boolean {
        return can(pageSlug, 'create');
    }

    /**
     * Check if user can update a page
     */
    function canUpdate(pageSlug: PermissionResource): boolean {
        return can(pageSlug, 'update');
    }

    /**
     * Check if user can delete from a page
     */
    function canDelete(pageSlug: PermissionResource): boolean {
        return can(pageSlug, 'delete');
    }

    /**
     * Check if user has any permission for a page
     */
    function canAccess(pageSlug: PermissionResource): boolean {
        return canRead(pageSlug) || canCreate(pageSlug) || canUpdate(pageSlug) || canDelete(pageSlug);
    }

    /**
     * Check if user has full access to a page
     */
    function hasFullAccess(pageSlug: PermissionResource): boolean {
        return canRead(pageSlug) && canCreate(pageSlug) && canUpdate(pageSlug) && canDelete(pageSlug);
    }

    return {
        permissions,
        can,
        canRead,
        canCreate,
        canUpdate,
        canDelete,
        canAccess,
        hasFullAccess,
    };
}
