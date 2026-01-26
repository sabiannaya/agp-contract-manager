<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { cn } from '@/lib/utils';

export type StatusBadgeVariant = 'complete' | 'incomplete' | 'active' | 'inactive' | 'default';

const props = defineProps<{
    variant?: StatusBadgeVariant;
    status?: string;
    active?: boolean;
    class?: HTMLAttributes['class'];
}>();

const { t } = useI18n();

// Resolve the effective variant from different prop sources
const effectiveVariant = computed<StatusBadgeVariant>(() => {
    // If variant is provided, use it directly
    if (props.variant) return props.variant;
    
    // If active prop is provided, map to active/inactive
    if (props.active !== undefined) {
        return props.active ? 'active' : 'inactive';
    }
    
    // If status prop is provided, map common status strings
    if (props.status) {
        const statusLower = props.status.toLowerCase();
        switch (statusLower) {
            case 'active':
                return 'active';
            case 'inactive':
                return 'inactive';
            case 'complete':
            case 'completed':
            case 'verified':
            case 'success':
                return 'complete';
            case 'incomplete':
            case 'pending':
            case 'failed':
            case 'error':
                return 'incomplete';
            default:
                return 'default';
        }
    }
    
    return 'default';
});

// Get the display label for the status
const statusLabel = computed(() => {
    if (props.status) {
        // Try to translate the status, fallback to raw status
        const key = `status.${props.status.toLowerCase()}`;
        const translated = t(key);
        // If translation key not found, capitalize the status
        return translated === key 
            ? props.status.charAt(0).toUpperCase() + props.status.slice(1).toLowerCase()
            : translated;
    }
    if (props.active !== undefined) {
        return props.active ? t('status.active') : t('status.inactive');
    }
    return null;
});

const variantClasses = computed(() => {
    switch (effectiveVariant.value) {
        case 'complete':
            return 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800';
        case 'incomplete':
            return 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800';
        case 'active':
            return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800';
        case 'inactive':
            return 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:border-gray-800';
        default:
            return 'bg-secondary text-secondary-foreground border-transparent';
    }
});
</script>

<template>
    <span
        data-slot="status-badge"
        :class="
            cn(
                'inline-flex items-center justify-center rounded-full border px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap gap-1.5',
                variantClasses,
                props.class,
            )
        "
    >
        <span
            v-if="effectiveVariant === 'complete' || effectiveVariant === 'incomplete'"
            :class="[
                'size-1.5 rounded-full',
                effectiveVariant === 'complete' ? 'bg-green-500' : 'bg-red-500',
            ]"
        />
        <slot>{{ statusLabel }}</slot>
    </span>
</template>
