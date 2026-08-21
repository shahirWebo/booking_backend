import {
    createAuthenticatedApiClient,
    createPublicApiClient,
} from '@/lib/api/client';

export type OtpRequestPayload = {
    mobile: string;
};

export type OtpRequestResponse = {
    expires_at: string;
    otp_request_id: string;
    resend_available_at: string;
};

export type OtpVerificationPayload = {
    code: string;
    otp_request_id: string;
};

export type AuthenticatedApiUser = {
    email: string | null;
    id: number;
    mobile_number: string;
    name: string;
    status: string;
};

export type OtpVerificationResponse = {
    access_token: string;
    token_type: 'Bearer';
};

type AuthApiServiceDependencies = {
    authenticatedClient?: ReturnType<typeof createAuthenticatedApiClient>;
    publicClient?: ReturnType<typeof createPublicApiClient>;
};

export function createAuthApiService(
    dependencies: AuthApiServiceDependencies = {},
) {
    const publicClient = dependencies.publicClient ?? publicApiClient;
    const authenticatedClient =
        dependencies.authenticatedClient ?? authenticatedApiClient;

    return {
        fetchCurrentUser: () =>
            authenticatedClient.get<AuthenticatedApiUser>('/auth/user'),
        logout: () => authenticatedClient.destroy('/auth/session'),
        requestOtp: (payload: OtpRequestPayload) =>
            publicClient.post<OtpRequestResponse, OtpRequestPayload>(
                '/auth/otp-requests',
                payload,
            ),
        verifyOtp: (payload: OtpVerificationPayload) =>
            publicClient.post<OtpVerificationResponse, OtpVerificationPayload>(
                '/auth/otp-verifications',
                payload,
            ),
    };
}

export const publicApiClient = createPublicApiClient();
export const authenticatedApiClient = createAuthenticatedApiClient();
export const authApiService = createAuthApiService();
