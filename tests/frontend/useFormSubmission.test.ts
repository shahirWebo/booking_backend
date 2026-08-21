import { describe, expect, it, vi } from 'vitest';

const toastMocks = vi.hoisted(() => ({
    showApiErrorToast: vi.fn(),
    showRetryableErrorToast: vi.fn(),
    showToast: vi.fn(),
}));

vi.mock('@/lib/toast', () => ({
    showApiErrorToast: toastMocks.showApiErrorToast,
    showRetryableErrorToast: toastMocks.showRetryableErrorToast,
    showToast: toastMocks.showToast,
}));

import {
    SubmissionValidationError,
    useFormSubmission,
} from '@/composables/useFormSubmission';

describe('useFormSubmission', () => {
    it('captures field validation errors without enabling retry', async () => {
        const submission = useFormSubmission();

        await submission.submit(() =>
            Promise.reject(
                new SubmissionValidationError({
                    email: 'Email is required.',
                }),
            ),
        );

        expect(submission.errorFor('email')).toBe('Email is required.');
        expect(submission.generalError.value).toBe(
            'Please correct the highlighted fields and try again.',
        );
        expect(submission.hasRetry.value).toBe(false);
        expect(toastMocks.showRetryableErrorToast).not.toHaveBeenCalled();
    });

    it('retries the last generic submission failure', async () => {
        const submission = useFormSubmission();
        const submitter = vi
            .fn<() => Promise<string>>()
            .mockRejectedValueOnce(new Error('offline'))
            .mockResolvedValueOnce('ok');

        await submission.submit(submitter, {
            errorMessage: 'The request could not be completed.',
        });

        expect(submission.hasRetry.value).toBe(true);
        expect(toastMocks.showRetryableErrorToast).toHaveBeenCalledTimes(1);

        await submission.retry();

        expect(submitter).toHaveBeenCalledTimes(2);
        expect(submission.generalError.value).toBeNull();
    });
});
