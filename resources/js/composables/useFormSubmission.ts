import { ref } from 'vue';
import { ApiClientError, MissingApiAccessTokenError } from '@/lib/api/client';
import {
    showApiErrorToast,
    showRetryableErrorToast,
    showToast,
} from '@/lib/toast';

export type SubmissionFieldErrors = Record<string, string[]>;

export class SubmissionValidationError extends Error {
    readonly fieldErrors: SubmissionFieldErrors;

    constructor(fieldErrors: Record<string, string | string[]>) {
        super('Please correct the highlighted fields and try again.');
        this.name = 'SubmissionValidationError';
        this.fieldErrors = normalizeFieldErrors(fieldErrors);
    }
}

type SubmitOptions<TResponse> = {
    errorMessage?: string;
    onError?: (error: unknown) => void;
    onSuccess?: (response: TResponse) => void;
    successMessage?: string;
};

type InertiaFormLike = {
    clearErrors: () => void;
    reset: (...fields: any[]) => unknown;
    submit: (...args: any[]) => void;
};

type InertiaSubmitDefinition = {
    method: string;
    url: string;
};

export function useFormSubmission() {
    const fieldErrors = ref<SubmissionFieldErrors>({});
    const generalError = ref<string | null>(null);
    const hasRetry = ref(false);
    const processing = ref(false);

    let retryHandler: (() => Promise<void>) | null = null;

    async function submit<TResponse>(
        submitter: () => Promise<TResponse>,
        options: SubmitOptions<TResponse> = {},
    ): Promise<TResponse | null> {
        processing.value = true;
        fieldErrors.value = {};
        generalError.value = null;
        hasRetry.value = false;
        retryHandler = async () => {
            await submit(submitter, options);
        };

        try {
            const response = await submitter();

            if (options.successMessage) {
                showToast({
                    type: 'success',
                    message: options.successMessage,
                });
            }

            options.onSuccess?.(response);

            return response;
        } catch (error) {
            handleSubmissionError(error, options.errorMessage);
            options.onError?.(error);

            return null;
        } finally {
            processing.value = false;
        }
    }

    async function retry(): Promise<void> {
        if (!retryHandler || processing.value) {
            return;
        }

        await retryHandler();
    }

    function errorFor(field: string): string | undefined {
        return fieldErrors.value[field]?.[0];
    }

    function handleSubmissionError(
        error: unknown,
        fallbackMessage?: string,
    ): void {
        if (error instanceof SubmissionValidationError) {
            fieldErrors.value = error.fieldErrors;
            generalError.value = error.message;

            return;
        }

        if (error instanceof ApiClientError) {
            fieldErrors.value = error.fieldErrors;
            generalError.value = error.message;
            hasRetry.value = true;
            showApiErrorToast(error, retry);

            return;
        }

        if (error instanceof MissingApiAccessTokenError) {
            generalError.value =
                'Your session needs to be restored before continuing.';
            hasRetry.value = true;
            showRetryableErrorToast(generalError.value, retry);

            return;
        }

        generalError.value =
            fallbackMessage ?? 'Something went wrong. Please try again.';
        hasRetry.value = true;
        showRetryableErrorToast(generalError.value, retry);
    }

    return {
        errorFor,
        fieldErrors,
        generalError,
        hasRetry,
        processing,
        retry,
        submit,
    };
}

export function submitInertiaForm(
    form: InertiaFormLike,
    route: InertiaSubmitDefinition,
    options: {
        resetOnSuccess?: string[];
    } = {},
): Promise<void> {
    form.clearErrors();

    return new Promise((resolve, reject) => {
        form.submit(route.method, route.url, {
            onCancel: () => reject(new Error('The submission was cancelled.')),
            onError: (errors: Record<string, string>) =>
                reject(new SubmissionValidationError(errors)),
            onSuccess: () => {
                if (options.resetOnSuccess?.length) {
                    form.reset(...options.resetOnSuccess);
                }

                resolve();
            },
        });
    });
}

function normalizeFieldErrors(
    fieldErrors: Record<string, string | string[]>,
): SubmissionFieldErrors {
    return Object.fromEntries(
        Object.entries(fieldErrors).map(([field, messages]) => [
            field,
            Array.isArray(messages) ? messages : [messages],
        ]),
    );
}
