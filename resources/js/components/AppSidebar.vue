<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as vendorsIndex } from '@/routes/vendors';
import { index as contractsIndex } from '@/routes/contracts';
import { index as ticketsIndex, view as ticketsView } from '@/routes/tickets';
import { index as paymentTrackerIndex } from '@/routes/payment-tracker';
import { index as rolesIndex } from '@/routes/roles';
import { index as usersIndex } from '@/routes/users';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { usePermission } from '@/composables/usePermission';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import {
    BookOpen,
    Folder,
    LayoutGrid,
    Building2,
    FileText,
    Ticket,
    ClipboardCheck,
    Shield,
    Users,
    Wallet,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const { canRead } = usePermission();
const { t } = useI18n();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: t('nav.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (canRead('vendors')) {
        items.push({
            title: t('nav.vendors'),
            href: vendorsIndex().url,
            icon: Building2,
        });
    }

    if (canRead('contracts')) {
        items.push({
            title: t('nav.contracts'),
            href: contractsIndex().url,
            icon: FileText,
        });
    }

    if (canRead('tickets')) {
        items.push({
            title: t('nav.tickets'),
            href: ticketsIndex().url,
            icon: Ticket,
        });
    }

    if (canRead('payment_tracker')) {
        items.push({
            title: t('nav.payment_tracker'),
            href: paymentTrackerIndex().url,
            icon: Wallet,
        });
    }

    if (canRead('role_groups')) {
        items.push({
            title: t('nav.roles'),
            href: rolesIndex().url,
            icon: Shield,
        });
    }

    if (canRead('users')) {
        items.push({
            title: t('nav.users'),
            href: usersIndex().url,
            icon: Users,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
