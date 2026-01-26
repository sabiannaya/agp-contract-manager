<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Shield, User as UserIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, updateRoles } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import type { User } from '@/types/auth';
import type { Role } from '@/types/models';

const props = defineProps<{
    user: User;
    roles: Role[];
}>();

const { t } = useI18n();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.users'), href: index().url },
    { title: props.user.name, href: '#' },
]);

// Get current role IDs
const currentRoleIds = computed(() =>
    props.user.roles?.map((r) => r.id) ?? [],
);

const form = useForm({
    roles: currentRoleIds.value,
});

function toggleRole(roleId: number) {
    const index = form.roles.indexOf(roleId);
    if (index === -1) {
        form.roles.push(roleId);
    } else {
        form.roles.splice(index, 1);
    }
}

function isRoleSelected(roleId: number): boolean {
    return form.roles.includes(roleId);
}

function submitForm() {
    form.put(updateRoles(props.user).url);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`${t('users.edit_roles')} - ${user.name}`" />

        <div class="max-w-2xl space-y-6">
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <Heading
                    :title="t('users.edit_roles')"
                    :description="t('users.edit_roles_description')"
                />
            </div>

            <!-- User Info Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-full bg-muted">
                            <UserIcon class="size-5 text-muted-foreground" />
                        </div>
                        <div>
                            <p>{{ user.name }}</p>
                            <p class="text-sm font-normal text-muted-foreground">
                                {{ user.email }}
                            </p>
                        </div>
                    </CardTitle>
                </CardHeader>
            </Card>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Roles Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('users.assign_roles') }}</CardTitle>
                        <CardDescription>{{ t('users.assign_roles_description') }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="role in roles"
                                :key="role.id"
                                :class="[
                                    'flex items-start gap-3 rounded-lg border p-4 transition-colors',
                                    isRoleSelected(role.id)
                                        ? 'border-primary bg-primary/5'
                                        : 'hover:bg-muted/50',
                                ]"
                            >
                                <Checkbox
                                    :id="`role-${role.id}`"
                                    :checked="isRoleSelected(role.id)"
                                    @update:checked="toggleRole(role.id)"
                                    :disabled="form.processing"
                                    class="mt-1"
                                />
                                <div class="flex-1 space-y-1">
                                    <Label
                                        :for="`role-${role.id}`"
                                        class="flex items-center gap-2 font-medium cursor-pointer"
                                    >
                                        <Shield class="size-4 text-muted-foreground" />
                                        {{ role.name }}
                                    </Label>
                                    <p
                                        v-if="role.description"
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ role.description }}
                                    </p>
                                </div>
                            </div>

                            <p
                                v-if="roles.length === 0"
                                class="text-center text-muted-foreground py-4"
                            >
                                {{ t('users.no_roles_available') }}
                            </p>
                        </div>

                        <p v-if="form.errors.roles" class="mt-2 text-sm text-destructive">
                            {{ form.errors.roles }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Submit buttons -->
                <div class="flex items-center justify-end gap-4">
                    <Link :href="index().url">
                        <Button type="button" variant="outline" :disabled="form.processing">
                            {{ t('common.cancel') }}
                        </Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        <Save class="mr-2 size-4" />
                        {{ form.processing ? t('common.saving') : t('common.save') }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
