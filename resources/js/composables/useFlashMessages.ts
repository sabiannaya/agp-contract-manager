import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { AppPageProps, FlashMessages } from '@/types';

export function useFlashMessages() {
    const page = usePage<AppPageProps>();

    const successMessage = ref<string | null>(null);
    const errorMessage = ref<string | null>(null);

    // Watch for flash messages from the server
    watch(
        () => page.props.flash,
        (flash: FlashMessages) => {
            if (flash?.success) {
                successMessage.value = flash.success;
                // Auto-clear after 5 seconds
                setTimeout(() => {
                    successMessage.value = null;
                }, 5000);
            }
            if (flash?.error) {
                errorMessage.value = flash.error;
                // Auto-clear after 5 seconds
                setTimeout(() => {
                    errorMessage.value = null;
                }, 5000);
            }
        },
        { immediate: true, deep: true },
    );

    function clearSuccess() {
        successMessage.value = null;
    }

    function clearError() {
        errorMessage.value = null;
    }

    function showSuccess(message: string, duration = 5000) {
        successMessage.value = message;
        if (duration > 0) {
            setTimeout(() => {
                successMessage.value = null;
            }, duration);
        }
    }

    function showError(message: string, duration = 5000) {
        errorMessage.value = message;
        if (duration > 0) {
            setTimeout(() => {
                errorMessage.value = null;
            }, duration);
        }
    }

    return {
        successMessage,
        errorMessage,
        clearSuccess,
        clearError,
        showSuccess,
        showError,
    };
}
