<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit, index } from '@/routes/roles';
import type { BreadcrumbItem } from '@/types';
import type { User } from '@/types/auth';
import type { RoleGroup } from '@/types/models';

const props = defineProps<{
    role: RoleGroup & {
        privileges: Array<{
            page_id: number;
            page: { id: number; name: string; slug: string; description: string };
            create: boolean;
            read: boolean;
            update: boolean;
            delete: boolean;
        }>;
        members: User[];
    };
}>();

const { t } = useI18n();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.roles'), href: index().url },
    { title: props.role.name, href: '#' },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="role.name" />

        <div class=" p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="index().url">
                        <Button variant="ghost" size="icon">
                            <ArrowLeft class="size-4" />
                        </Button>
                    </Link>
                    <Heading :title="role.name" :description="role.description ?? undefined" />
                </div>
                <Link :href="edit(role.id).url">
                    <Button>{{ t('common.edit') }}</Button>
                </Link>
            </div>

            <!-- Basic Info Card -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('roles.details') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">{{ t('roles.name') }}</p>
                            <p class="text-base">{{ role.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">{{ t('roles.status') }}</p>
                            <Badge :variant="role.is_active ? 'default' : 'secondary'">
                                {{ role.is_active ? t('common.active') : t('common.inactive') }}
                            </Badge>
                        </div>
                    </div>
                    <div v-if="role.description">
                        <p class="text-sm font-medium text-muted-foreground">{{ t('roles.description_field') }}</p>
                        <p class="text-base">{{ role.description }}</p>
                    </div>
                    <div v-if="role.is_system">
                        <Badge variant="outline">{{ t('roles.system_role') }}</Badge>
                    </div>
                </CardContent>
            </Card>

            <!-- Privileges Card -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('roles.page_privileges') }}</CardTitle>
                    <CardDescription>{{ t('roles.privileges_granted') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="privilege in role.privileges"
                            :key="privilege.page_id"
                            class="rounded-lg border p-4"
                        >
                            <div class="mb-3">
                                <h4 class="font-medium">{{ privilege.page.name }}</h4>
                                <p class="text-sm text-muted-foreground">{{ privilege.page.description }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 v-if="privilege.create" class="size-5 text-green-600" />
                                    <XCircle v-else class="size-5 text-gray-400" />
                                    <span class="text-sm">{{ t('common.create') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 v-if="privilege.read" class="size-5 text-green-600" />
                                    <XCircle v-else class="size-5 text-gray-400" />
                                    <span class="text-sm">{{ t('common.read') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 v-if="privilege.update" class="size-5 text-green-600" />
                                    <XCircle v-else class="size-5 text-gray-400" />
                                    <span class="text-sm">{{ t('common.update') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 v-if="privilege.delete" class="size-5 text-green-600" />
                                    <XCircle v-else class="size-5 text-gray-400" />
                                    <span class="text-sm">{{ t('common.delete') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Members Card -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('roles.members') }}</CardTitle>
                    <CardDescription>{{ t('roles.members_description', { count: role.members.length }) }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="role.members.length > 0" class="space-y-2">
                        <div
                            v-for="member in role.members"
                            :key="member.id"
                            class="rounded-lg border p-3"
                        >
                            <p class="font-medium">{{ member.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ member.email }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">{{ t('roles.no_members') }}</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
