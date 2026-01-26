<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { ref, computed } from 'vue';
import { Upload, X, FileText, Image } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    modelValue?: File | null;
    accept?: string;
    maxSize?: number; // in KB
    class?: HTMLAttributes['class'];
    disabled?: boolean;
    existingFile?: {
        name: string;
        url?: string;
    } | null;
}>();

const emits = defineEmits<{
    (e: 'update:modelValue', file: File | null): void;
    (e: 'remove'): void;
}>();

const isDragging = ref(false);
const inputRef = ref<HTMLInputElement | null>(null);
const error = ref<string | null>(null);

const acceptedTypes = computed(() => props.accept ?? '.pdf,.jpg,.jpeg,.png');
const maxSizeKB = computed(() => props.maxSize ?? 10240); // 10MB default

const currentFileName = computed(() => {
    if (props.modelValue) {
        return props.modelValue.name;
    }
    if (props.existingFile) {
        return props.existingFile.name;
    }
    return null;
});

const fileIcon = computed(() => {
    const name = currentFileName.value?.toLowerCase() ?? '';
    if (name.endsWith('.jpg') || name.endsWith('.jpeg') || name.endsWith('.png')) {
        return Image;
    }
    return FileText;
});

function validateFile(file: File): boolean {
    error.value = null;

    // Check file size
    if (file.size > maxSizeKB.value * 1024) {
        error.value = `File size exceeds ${Math.round(maxSizeKB.value / 1024)}MB limit`;
        return false;
    }

    // Check file type
    const allowedExtensions = acceptedTypes.value.split(',').map((ext) => ext.trim().toLowerCase());
    const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase();
    const mimeType = file.type.toLowerCase();

    const isValidExtension = allowedExtensions.some(
        (ext) => ext === fileExtension || ext === mimeType || ext === '*',
    );

    if (!isValidExtension) {
        error.value = `File type not allowed. Accepted: ${acceptedTypes.value}`;
        return false;
    }

    return true;
}

function handleFile(file: File) {
    if (validateFile(file)) {
        emits('update:modelValue', file);
    }
}

function handleDrop(e: DragEvent) {
    isDragging.value = false;
    if (props.disabled) return;

    const file = e.dataTransfer?.files[0];
    if (file) {
        handleFile(file);
    }
}

function handleFileInput(e: Event) {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        handleFile(file);
    }
    // Reset input so same file can be selected again
    target.value = '';
}

function handleRemove() {
    emits('update:modelValue', null);
    emits('remove');
    error.value = null;
}

function openFilePicker() {
    if (!props.disabled) {
        inputRef.value?.click();
    }
}
</script>

<template>
    <div :class="cn('w-full', props.class)">
        <!-- File selected state -->
        <div
            v-if="currentFileName"
            :class="
                cn(
                    'flex items-center justify-between gap-2 rounded-md border p-3',
                    'border-input bg-muted/30',
                )
            "
        >
            <div class="flex items-center gap-2 min-w-0">
                <component :is="fileIcon" class="size-4 text-muted-foreground shrink-0" />
                <span class="text-sm truncate">{{ currentFileName }}</span>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <Button
                    v-if="existingFile?.url"
                    type="button"
                    variant="ghost"
                    size="sm"
                    as="a"
                    :href="existingFile.url"
                    target="_blank"
                >
                    Preview
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    :disabled="disabled"
                    @click="handleRemove"
                >
                    <X class="size-4" />
                </Button>
            </div>
        </div>

        <!-- Upload area -->
        <div
            v-else
            :class="
                cn(
                    'relative flex flex-col items-center justify-center gap-2 rounded-md border-2 border-dashed p-6 transition-colors cursor-pointer',
                    'border-input hover:border-primary/50',
                    isDragging && 'border-primary bg-primary/5',
                    disabled && 'cursor-not-allowed opacity-50',
                    error && 'border-destructive',
                )
            "
            @click="openFilePicker"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
        >
            <Upload class="size-8 text-muted-foreground" />
            <div class="text-center">
                <p class="text-sm font-medium">
                    Drop file here or click to upload
                </p>
                <p class="text-xs text-muted-foreground mt-1">
                    {{ acceptedTypes }} (max {{ Math.round(maxSizeKB / 1024) }}MB)
                </p>
            </div>
            <input
                ref="inputRef"
                type="file"
                :accept="acceptedTypes"
                :disabled="disabled"
                class="hidden"
                @change="handleFileInput"
            />
        </div>

        <!-- Error message -->
        <p v-if="error" class="mt-1.5 text-xs text-destructive">
            {{ error }}
        </p>
    </div>
</template>
