import { toast } from 'vue-sonner';
import type { ApiClientError } from '@/lib/api/client';
import type { FlashToast } from '@/types/ui';

export function showToast(payload: FlashToast): void {
    toast[payload.type](payload.message, {
        action: payload.action,
        description: payload.description,
    });
}

export function showRetryableErrorToast(
    message: string,
    retry: () => void,
    description?: string,
): void {
    showToast({
        type: 'error',
        message,
        description,
        action: {
            label: 'Retry',
            onClick: retry,
        },
    });
}

export function showApiErrorToast(
    error: Pick<ApiClientError, 'message' | 'status'>,
    retry?: () => void,
): void {
    if (retry) {
        showRetryableErrorToast(
            error.message,
            retry,
            `Request failed with status ${error.status}.`,
        );

        return;
    }

    showToast({
        type: 'error',
        message: error.message,
        description: `Request failed with status ${error.status}.`,
    });
}
