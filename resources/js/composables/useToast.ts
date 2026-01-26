import { reactive } from 'vue';
import type { ToastProps } from '@/components/ui/toast/Toast.vue';

interface ToastState {
    toasts: ToastProps[];
}

const state = reactive<ToastState>({
    toasts: [],
});

let toastId = 0;

export interface ToastOptions {
    title?: string;
    message: string;
    type?: 'success' | 'error' | 'info' | 'warning';
    duration?: number;
}

export function useToast() {
    function addToast(options: ToastOptions) {
        const id = `toast-${++toastId}`;
        const toast: ToastProps = {
            id,
            type: options.type || 'info',
            title: options.title,
            message: options.message,
            duration: options.duration ?? 5000,
        };

        state.toasts.push(toast);

        return id;
    }

    function removeToast(id: string) {
        const index = state.toasts.findIndex((t) => t.id === id);
        if (index > -1) {
            state.toasts.splice(index, 1);
        }
    }

    function success(message: string, title?: string, duration?: number) {
        return addToast({ message, title, type: 'success', duration });
    }

    function error(message: string, title?: string, duration?: number) {
        return addToast({ message, title, type: 'error', duration });
    }

    function info(message: string, title?: string, duration?: number) {
        return addToast({ message, title, type: 'info', duration });
    }

    function warning(message: string, title?: string, duration?: number) {
        return addToast({ message, title, type: 'warning', duration });
    }

    function clear() {
        state.toasts = [];
    }

    return {
        toasts: state.toasts,
        addToast,
        removeToast,
        success,
        error,
        info,
        warning,
        clear,
    };
}
