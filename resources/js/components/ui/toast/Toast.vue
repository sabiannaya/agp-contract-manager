<script setup lang="ts">
import { AlertCircle, CheckCircle2, Info, X, XCircle } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

export interface ToastProps {
    id: string;
    type?: 'success' | 'error' | 'info' | 'warning';
    title?: string;
    message: string;
    duration?: number;
}

const props = withDefaults(defineProps<ToastProps>(), {
    type: 'info',
    duration: 5000,
});

const emit = defineEmits<{
    close: [id: string];
}>();

const isVisible = ref(false);

const iconComponent = computed(() => {
    switch (props.type) {
        case 'success':
            return CheckCircle2;
        case 'error':
            return XCircle;
        case 'warning':
            return AlertCircle;
        case 'info':
        default:
            return Info;
    }
});

const colorClasses = computed(() => {
    switch (props.type) {
        case 'success':
            return 'bg-green-50 dark:bg-green-950/30 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200';
        case 'error':
            return 'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200';
        case 'warning':
            return 'bg-yellow-50 dark:bg-yellow-950/30 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200';
        case 'info':
        default:
            return 'bg-blue-50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200';
    }
});

const iconColorClasses = computed(() => {
    switch (props.type) {
        case 'success':
            return 'text-green-600 dark:text-green-400';
        case 'error':
            return 'text-red-600 dark:text-red-400';
        case 'warning':
            return 'text-yellow-600 dark:text-yellow-400';
        case 'info':
        default:
            return 'text-blue-600 dark:text-blue-400';
    }
});

function close() {
    isVisible.value = false;
    setTimeout(() => {
        emit('close', props.id);
    }, 300);
}

onMounted(() => {
    requestAnimationFrame(() => {
        isVisible.value = true;
    });

    if (props.duration > 0) {
        setTimeout(close, props.duration);
    }
});
</script>

<template>
    <div
        :class="[
            'pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg border shadow-lg transition-all duration-300',
            colorClasses,
            isVisible ? 'translate-x-0 opacity-100' : 'translate-x-full opacity-0',
        ]"
    >
        <div class="p-4">
            <div class="flex items-start gap-3">
                <component :is="iconComponent" :class="['size-5 shrink-0 mt-0.5', iconColorClasses]" />
                <div class="flex-1 pt-0.5">
                    <p v-if="title" class="font-semibold text-sm">{{ title }}</p>
                    <p :class="['text-sm', title ? 'mt-1' : '']">{{ message }}</p>
                </div>
                <button
                    type="button"
                    @click="close"
                    class="shrink-0 rounded-md p-1.5 hover:bg-black/5 dark:hover:bg-white/5 transition-colors"
                >
                    <X class="size-4" />
                    <span class="sr-only">Close</span>
                </button>
            </div>
        </div>
    </div>
</template>
