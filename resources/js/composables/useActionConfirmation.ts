import { ref } from 'vue';

export interface ConfirmationDetail {
    label: string;
    value: string;
}

export interface ConfirmationOptions {
    title: string;
    description?: string;
    confirmText?: string;
    cancelText?: string;
    destructive?: boolean;
    details?: ConfirmationDetail[];
}

export function useActionConfirmation() {
    const open = ref(false);
    const title = ref('');
    const description = ref('');
    const confirmText = ref('Confirm');
    const cancelText = ref('Cancel');
    const destructive = ref(false);
    const details = ref<ConfirmationDetail[]>([]);

    let pendingAction: (() => void) | null = null;

    function requestConfirmation(options: ConfirmationOptions, action: () => void) {
        title.value = options.title;
        description.value = options.description ?? '';
        confirmText.value = options.confirmText ?? 'Confirm';
        cancelText.value = options.cancelText ?? 'Cancel';
        destructive.value = options.destructive ?? false;
        details.value = options.details ?? [];
        pendingAction = action;
        open.value = true;
    }

    function confirm() {
        open.value = false;
        const action = pendingAction;
        pendingAction = null;
        action?.();
    }

    function cancel() {
        open.value = false;
        pendingAction = null;
    }

    return {
        open,
        title,
        description,
        confirmText,
        cancelText,
        destructive,
        details,
        requestConfirmation,
        confirm,
        cancel,
    };
}
