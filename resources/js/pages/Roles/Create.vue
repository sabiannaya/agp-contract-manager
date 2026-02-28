<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import ActionConfirmationDialog from '@/components/ActionConfirmationDialog.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useActionConfirmation } from '@/composables/useActionConfirmation';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store } from '@/routes/roles';
import type { BreadcrumbItem } from '@/types';
import type { Page, RoleGroupForm } from '@/types/models';

const props = defineProps<{
    pages: Page[];
}>();

const { t } = useI18n();
const saveConfirmation = useActionConfirmation();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.roles'), href: index().url },
    { title: t('roles.create'), href: '#' },
]);

// Initialize form with empty privileges for each page
const initialPrivileges = props.pages.map((page) => ({
    page_id: page.id,
    create: false,
    read: false,
    update: false,
    delete: false,
}));

const form = useForm<RoleGroupForm>({
    name: '',
    description: '',
    is_active: true,
    privileges: initialPrivileges,
});

function executeSubmitForm() {
    form.post(store().url, {
        onSuccess: () => {
            router.visit(index().url);
        },
    });
}

function submitForm() {
    saveConfirmation.requestConfirmation(
        {
            title: t('common.create'),
            description: t('roles.create_description'),
            confirmText: t('common.save'),
            details: [
                { label: t('roles.name'), value: form.name || '-' },
            ],
        },
        executeSubmitForm,
    );
}

function toggleAllForPage(pageId: number, value: boolean) {
    const index = form.privileges.findIndex((p) => p.page_id === pageId);
    if (index !== -1) {
        form.privileges[index].create = value;
        form.privileges[index].read = value;
        form.privileges[index].update = value;
        form.privileges[index].delete = value;
    }
}

function getPrivilege(pageId: number) {
    return form.privileges.find((p) => p.page_id === pageId);
}

function updatePrivilege(pageId: number, field: 'create' | 'read' | 'update' | 'delete', value: boolean) {
    const index = form.privileges.findIndex((p) => p.page_id === pageId);
    if (index !== -1) {
        form.privileges[index][field] = value;
    }
}

function isAllSelected(pageId: number): boolean {
    const privilege = form.privileges.find((p) => p.page_id === pageId);
    return privilege
        ? privilege.create &&
              privilege.read &&
              privilege.update &&
              privilege.delete
        : false;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('roles.create')" />

        <div class="p-6">
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <Heading
                    :title="t('roles.create')"
                    :description="t('roles.create_description')"
                />
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Basic Info Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('roles.basic_info') }}</CardTitle>
                        <CardDescription>{{
                            t('roles.basic_info_description')
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="name">{{ t('roles.name') }}</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    :disabled="form.processing"
                                    required
                                />
                                <p
                                    v-if="form.errors.name"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="flex items-center space-x-2 pt-6">
                                <Checkbox
                                    id="is_active"
                                    :checked="form.is_active"
                                    @update:checked="form.is_active = $event"
                                    :disabled="form.processing"
                                />
                                <Label for="is_active" class="font-normal">
                                    {{ t('roles.is_active') }}
                                </Label>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="description">{{
                                t('roles.description_field')
                            }}</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                :disabled="form.processing"
                                :rows="2"
                            />
                            <p
                                v-if="form.errors.description"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.description }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Privileges Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('roles.page_privileges') }}</CardTitle>
                        <CardDescription>{{
                            t('roles.page_privileges_description')
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-4 space-y-4">
                            <div
                                v-for="page in pages"
                                :key="page.id"
                                class="rounded-lg border p-4"
                            >
                                <div
                                    class="mb-3 flex items-center justify-between"
                                >
                                    <div>
                                        <h4 class="font-medium">
                                            {{ page.name }}
                                        </h4>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ page.description }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Checkbox
                                            :checked="isAllSelected(page.id)"
                                            @update:checked="
                                                toggleAllForPage(
                                                    page.id,
                                                    $event,
                                                )
                                            "
                                            :disabled="form.processing"
                                        />
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >{{ t('common.select_all') }}</span
                                        >
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-4">
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            :id="`${page.id}-create`"
                                            :checked="getPrivilege(page.id)?.create ?? false"
                                            @update:checked="updatePrivilege(page.id, 'create', $event)"
                                            :disabled="form.processing"
                                        />
                                        <Label
                                            :for="`${page.id}-create`"
                                            class="text-sm font-normal"
                                        >
                                            {{ t('common.create') }}
                                        </Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            :id="`${page.id}-read`"
                                            :checked="getPrivilege(page.id)?.read ?? false"
                                            @update:checked="updatePrivilege(page.id, 'read', $event)"
                                            :disabled="form.processing"
                                        />
                                        <Label
                                            :for="`${page.id}-read`"
                                            class="text-sm font-normal"
                                        >
                                            {{ t('common.read') }}
                                        </Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            :id="`${page.id}-update`"
                                            :checked="getPrivilege(page.id)?.update ?? false"
                                            @update:checked="updatePrivilege(page.id, 'update', $event)"
                                            :disabled="form.processing"
                                        />
                                        <Label
                                            :for="`${page.id}-update`"
                                            class="text-sm font-normal"
                                        >
                                            {{ t('common.update') }}
                                        </Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            :id="`${page.id}-delete`"
                                            :checked="getPrivilege(page.id)?.delete ?? false"
                                            @update:checked="updatePrivilege(page.id, 'delete', $event)"
                                            :disabled="form.processing"
                                        />
                                        <Label
                                            :for="`${page.id}-delete`"
                                            class="text-sm font-normal"
                                        >
                                            {{ t('common.delete') }}
                                        </Label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p
                            v-if="form.errors.privileges"
                            class="mt-2 text-sm text-destructive"
                        >
                            {{ form.errors.privileges }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Submit buttons -->
                <div class="flex items-center justify-end gap-4">
                    <Link :href="index().url">
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        <Save class="mr-2 size-4" />
                        {{
                            form.processing
                                ? t('common.saving')
                                : t('common.save')
                        }}
                    </Button>
                </div>
            </form>

            <ActionConfirmationDialog
                v-model:open="saveConfirmation.open"
                :title="saveConfirmation.title"
                :description="saveConfirmation.description"
                :confirm-text="saveConfirmation.confirmText"
                :cancel-text="saveConfirmation.cancelText"
                :destructive="saveConfirmation.destructive"
                :details="saveConfirmation.details"
                @confirm="saveConfirmation.confirm"
                @cancel="saveConfirmation.cancel"
            />
        </div>
    </AppLayout>
</template>
