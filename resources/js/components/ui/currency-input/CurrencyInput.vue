<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: number | null;
        class?: HTMLAttributes['class'];
        placeholder?: string;
        disabled?: boolean;
        min?: number;
        max?: number;
    }>(),
    {
        modelValue: null,
        placeholder: '0',
        disabled: false,
    },
);

const emits = defineEmits<{
    (e: 'update:modelValue', payload: number | null): void;
}>();

/**
 * Format a number with dot as thousands separator (Indonesian locale).
 * Example: 1000000 → "1.000.000"
 */
function formatWithThousands(value: number | null | undefined): string {
    if (value == null || isNaN(value)) return '';
    // Use integer display with dot separator
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
}

/**
 * Parse a formatted string back to a number.
 * Removes dots (thousands separator) and handles empty string.
 */
function parseFormatted(formatted: string): number | null {
    const cleaned = formatted.replace(/\./g, '').replace(/,/g, '').trim();
    if (cleaned === '' || cleaned === '-') return null;
    const num = Number(cleaned);
    return isNaN(num) ? null : num;
}

const displayValue = ref(formatWithThousands(props.modelValue));

// Sync from parent → display
watch(
    () => props.modelValue,
    (newVal) => {
        const parsed = parseFormatted(displayValue.value);
        // Only update display if the value actually changed (avoid cursor jump)
        if (parsed !== newVal) {
            displayValue.value = formatWithThousands(newVal);
        }
    },
);

function onInput(event: Event) {
    const input = event.target as HTMLInputElement;
    const cursorPos = input.selectionStart ?? 0;
    const oldLength = input.value.length;

    // Strip non-digit characters except minus
    const raw = input.value.replace(/[^\d-]/g, '');
    const numericValue = raw === '' || raw === '-' ? null : Number(raw);

    // Clamp to min/max
    let clampedValue = numericValue;
    if (clampedValue != null) {
        if (props.min != null && clampedValue < props.min) clampedValue = props.min;
        if (props.max != null && clampedValue > props.max) clampedValue = props.max;
    }

    emits('update:modelValue', clampedValue);

    // Reformat display
    const formatted = formatWithThousands(clampedValue);
    displayValue.value = formatted;

    // Restore cursor position accounting for added/removed dots
    const newLength = formatted.length;
    const diff = newLength - oldLength;
    const newPos = Math.max(0, cursorPos + diff);

    requestAnimationFrame(() => {
        input.value = formatted;
        input.setSelectionRange(newPos, newPos);
    });
}

function onBlur() {
    // Ensure clean formatting on blur
    displayValue.value = formatWithThousands(props.modelValue);
}
</script>

<template>
    <input
        :value="displayValue"
        :placeholder="placeholder"
        :disabled="disabled"
        inputmode="numeric"
        data-slot="input"
        :class="
            cn(
                'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                props.class,
            )
        "
        @input="onInput"
        @blur="onBlur"
    />
</template>
