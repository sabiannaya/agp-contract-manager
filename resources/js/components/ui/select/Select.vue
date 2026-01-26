<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed, ref, watch } from 'vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxPortal,
    ComboboxRoot,
    ComboboxTrigger,
    ComboboxViewport,
} from 'reka-ui';
import { Check, ChevronDown, X } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

export interface SelectOption {
    value: string | number;
    label: string;
    disabled?: boolean;
}

const props = defineProps<{
    modelValue?: string | number | null;
    options: SelectOption[];
    placeholder?: string;
    searchPlaceholder?: string;
    disabled?: boolean;
    class?: HTMLAttributes['class'];
    emptyText?: string;
    clearable?: boolean;
}>();

const emits = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
}>();

const searchQuery = ref('');
const open = ref(false);

const selectedOption = computed(() => {
    return props.options.find((opt) => opt.value === props.modelValue);
});

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter((opt) => opt.label.toLowerCase().includes(query));
});

function handleSelect(option: SelectOption) {
    emits('update:modelValue', option.value);
    open.value = false;
    searchQuery.value = '';
}

function handleClear() {
    emits('update:modelValue', null);
    searchQuery.value = '';
}

watch(open, (isOpen) => {
    if (!isOpen) {
        searchQuery.value = '';
    }
});
</script>

<template>
    <ComboboxRoot
        v-model:open="open"
        :disabled="disabled"
        :class="cn('relative w-full', props.class)"
    >
        <ComboboxAnchor
            :class="
                cn(
                    'flex h-9 w-full items-center justify-between rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs',
                    'border-input dark:bg-input/30',
                    'focus-within:border-ring focus-within:ring-ring/50 focus-within:ring-[3px]',
                    disabled && 'cursor-not-allowed opacity-50',
                )
            "
        >
            <ComboboxInput
                v-model="searchQuery"
                :placeholder="selectedOption?.label ?? placeholder ?? 'Select...'"
                :disabled="disabled"
                :class="
                    cn(
                        'flex-1 bg-transparent outline-none placeholder:text-muted-foreground text-sm',
                        selectedOption && !open && 'text-foreground',
                        !selectedOption && 'text-muted-foreground',
                    )
                "
                @focus="open = true"
            />
            <div class="flex items-center gap-1">
                <button
                    v-if="clearable && selectedOption"
                    type="button"
                    class="text-muted-foreground hover:text-foreground"
                    @click.stop="handleClear"
                >
                    <X class="size-4" />
                </button>
                <ComboboxTrigger class="text-muted-foreground">
                    <ChevronDown class="size-4" />
                </ComboboxTrigger>
            </div>
        </ComboboxAnchor>

        <ComboboxPortal>
            <ComboboxContent
                :class="
                    cn(
                        'z-50 max-h-60 min-w-[var(--reka-combobox-trigger-width)] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md',
                        'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
                    )
                "
                position="popper"
                :side-offset="4"
            >
            <ComboboxViewport class="p-1">
                <ComboboxEmpty class="py-6 text-center text-sm text-muted-foreground">
                    {{ emptyText ?? 'No results found.' }}
                </ComboboxEmpty>
                <ComboboxItem
                    v-for="option in filteredOptions"
                    :key="option.value"
                    :value="option"
                    :disabled="option.disabled"
                    :class="
                        cn(
                            'relative flex w-full cursor-pointer select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none',
                            'data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground',
                            'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                        )
                    "
                    @select="handleSelect(option)"
                >
                    <span class="absolute left-2 flex size-3.5 items-center justify-center">
                        <ComboboxItemIndicator>
                            <Check class="size-4" />
                        </ComboboxItemIndicator>
                    </span>
                    {{ option.label }}
                </ComboboxItem>
            </ComboboxViewport>
        </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>
</template>
