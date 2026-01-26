<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { cn } from '@/lib/utils';
import { FileUpload } from '@/components/ui/file-upload';
import { StatusBadge } from '@/components/ui/status-badge';
import { Label } from '@/components/ui/label';
import type { Document, DocumentType } from '@/types';
import { DOCUMENT_TYPES } from '@/types';

const props = defineProps<{
    documents: Document[];
    files: Record<DocumentType, File | null>;
    disabled?: boolean;
    readonly?: boolean;
    class?: HTMLAttributes['class'];
}>();

const emits = defineEmits<{
    (e: 'update:files', files: Record<DocumentType, File | null>): void;
    (e: 'remove', type: DocumentType): void;
}>();

const { t } = useI18n();

const completeness = computed(() => {
    const uploadedCount = DOCUMENT_TYPES.filter((type) => {
        const hasExisting = props.documents.some((d) => d.type === type);
        const hasNew = props.files[type];
        return hasExisting || hasNew;
    }).length;

    return {
        count: uploadedCount,
        total: DOCUMENT_TYPES.length,
        isComplete: uploadedCount >= DOCUMENT_TYPES.length,
    };
});

function getExistingDocument(type: DocumentType): Document | undefined {
    return props.documents.find((d) => d.type === type);
}

function handleFileChange(type: DocumentType, file: File | null) {
    const newFiles = { ...props.files, [type]: file };
    emits('update:files', newFiles);
}

function handleRemove(type: DocumentType) {
    handleFileChange(type, null);
    emits('remove', type);
}
</script>

<template>
    <div :class="cn('space-y-4', props.class)">
        <!-- Completeness indicator -->
        <div class="flex items-center justify-between">
            <Label class="text-base font-semibold">{{ t('documents.title') }}</Label>
            <StatusBadge :variant="completeness.isComplete ? 'complete' : 'incomplete'">
                {{ completeness.count }}/{{ completeness.total }}
                {{ t('tickets.completeness') }}
            </StatusBadge>
        </div>

        <!-- Document grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
                v-for="type in DOCUMENT_TYPES"
                :key="type"
                class="space-y-2"
            >
                <Label>{{ t(`documents.${type}`) }}</Label>

                <!-- Readonly mode -->
                <div
                    v-if="readonly"
                    :class="
                        cn(
                            'flex items-center justify-between gap-2 rounded-md border p-3',
                            'border-input bg-muted/30',
                        )
                    "
                >
                    <span class="text-sm">
                        {{ getExistingDocument(type)?.original_name ?? t('documents.noFile') }}
                    </span>
                    <a
                        v-if="getExistingDocument(type)"
                        :href="`/documents/${getExistingDocument(type)?.id}/preview`"
                        target="_blank"
                        class="text-sm text-primary hover:underline"
                    >
                        {{ t('common.preview') }}
                    </a>
                </div>

                <!-- Editable mode -->
                <FileUpload
                    v-else
                    :model-value="files[type]"
                    :existing-file="
                        getExistingDocument(type)
                            ? {
                                  name: getExistingDocument(type)!.original_name,
                                  url: `/documents/${getExistingDocument(type)!.id}/preview`,
                              }
                            : null
                    "
                    :disabled="disabled"
                    accept=".pdf,.jpg,.jpeg,.png"
                    :max-size="10240"
                    @update:model-value="handleFileChange(type, $event)"
                    @remove="handleRemove(type)"
                />
            </div>
        </div>

        <!-- File type hint -->
        <p class="text-xs text-muted-foreground">
            {{ t('documents.fileTypes') }}
        </p>
    </div>
</template>
