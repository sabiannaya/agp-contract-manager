<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { ConfirmationDetail } from '@/composables/useActionConfirmation';

defineProps<{
    open: boolean;
    title: string;
    description?: string;
    confirmText?: string;
    cancelText?: string;
    destructive?: boolean;
    details?: ConfirmationDetail[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();

function handleCancel() {
    emit('cancel');
    emit('update:open', false);
}

function handleConfirm() {
    emit('confirm');
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-[520px]">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="details?.length" class="rounded-md border bg-muted/40 p-3 space-y-2">
                <div
                    v-for="detail in details"
                    :key="`${detail.label}-${detail.value}`"
                    class="flex items-start justify-between gap-4 text-sm"
                >
                    <span class="text-muted-foreground">{{ detail.label }}</span>
                    <span class="text-right font-medium break-all">{{ detail.value }}</span>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="handleCancel">
                    {{ cancelText ?? 'Cancel' }}
                </Button>
                <Button
                    type="button"
                    :variant="destructive ? 'destructive' : 'default'"
                    @click="handleConfirm"
                >
                    {{ confirmText ?? 'Confirm' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
