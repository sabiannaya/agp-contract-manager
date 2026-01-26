<script setup lang="ts">
import { computed } from 'vue';
import Toast, { type ToastProps } from './Toast.vue';

export interface ToastContainerProps {
    position?: 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left' | 'top-center' | 'bottom-center';
}

const props = withDefaults(defineProps<ToastContainerProps & { toasts: ToastProps[] }>(), {
    position: 'bottom-right',
});

const emit = defineEmits<{
    remove: [id: string];
}>();

const positionClasses = computed(() => {
    switch (props.position) {
        case 'top-right':
            return 'top-0 right-0 items-end';
        case 'top-left':
            return 'top-0 left-0 items-start';
        case 'bottom-right':
            return 'bottom-0 right-0 items-end';
        case 'bottom-left':
            return 'bottom-0 left-0 items-start';
        case 'top-center':
            return 'top-0 left-1/2 -translate-x-1/2 items-center';
        case 'bottom-center':
            return 'bottom-0 left-1/2 -translate-x-1/2 items-center';
        default:
            return 'bottom-0 right-0 items-end';
    }
});

function removeToast(id: string) {
    emit('remove', id);
}
</script>

<template>
    <div
        :class="[
            'pointer-events-none fixed z-[100] flex max-h-screen w-full flex-col gap-2 p-4 sm:p-6',
            positionClasses,
        ]"
    >
        <TransitionGroup
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-300"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <Toast
                v-for="toast in toasts"
                :key="toast.id"
                v-bind="toast"
                @close="removeToast"
            />
        </TransitionGroup>
    </div>
</template>
