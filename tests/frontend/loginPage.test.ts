import { usePage } from '@inertiajs/vue3';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiClientError } from '@/lib/api/client';
import LoginPage from '@/pages/auth/Login.vue';

const authApiMocks = vi.hoisted(() => ({
    fetchCurrentUser: vi.fn(),
    requestOtp: vi.fn(),
    verifyOtp: vi.fn(),
}));

const browserSessionMocks = vi.hoisted(() => ({
    clearBrowserSession: vi.fn(),
    getBrowserSessionState: vi.fn(),
    initializeBrowserSession: vi.fn(),
    persistBrowserTokenSession: vi.fn(),
    resolveBrowserSessionAuth: vi.fn(),
}));

vi.mock('@/lib/api/auth', () => ({
    authApiService: authApiMocks,
}));

vi.mock('@/lib/browserSession', () => ({
    clearBrowserSession: browserSessionMocks.clearBrowserSession,
    getBrowserSessionState: browserSessionMocks.getBrowserSessionState,
    initializeBrowserSession: browserSessionMocks.initializeBrowserSession,
    persistBrowserTokenSession: browserSessionMocks.persistBrowserTokenSession,
    resolveBrowserSessionAuth: browserSessionMocks.resolveBrowserSessionAuth,
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: {
        name: 'Head',
        props: ['title'],
        template: '<div><slot /></div>',
    },
    usePage: vi.fn(),
}));

const loginPageStubs = {
    AppLogoIcon: {
        template: '<div data-test="logo" />',
    },
    InputOTP: {
        props: ['modelValue'],
        emits: ['update:modelValue'],
        template:
            '<input data-test="otp-input" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
    },
    InputOTPGroup: {
        template: '<div><slot /></div>',
    },
    InputOTPSlot: {
        template: '<span />',
    },
};

describe('LoginPage', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-08-21T10:00:00Z'));
        vi.mocked(usePage).mockReturnValue({
            props: {
                intendedUrl: null,
                auth: {
                    user: null,
                    roles: [],
                    permissions: [],
                    preferredSurface: null,
                    sessionMode: 'guest',
                },
            },
        } as ReturnType<typeof usePage>);
        authApiMocks.fetchCurrentUser.mockReset();
        authApiMocks.requestOtp.mockReset();
        authApiMocks.verifyOtp.mockReset();
        browserSessionMocks.clearBrowserSession.mockReset();
        browserSessionMocks.getBrowserSessionState.mockReset();
        browserSessionMocks.initializeBrowserSession.mockReset();
        browserSessionMocks.persistBrowserTokenSession.mockReset();
        browserSessionMocks.resolveBrowserSessionAuth.mockReset();
        browserSessionMocks.getBrowserSessionState.mockReturnValue({
            hasRestored: false,
            isRestoring: false,
        });
        browserSessionMocks.resolveBrowserSessionAuth.mockImplementation(
            (auth) => auth,
        );
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    it('requests an OTP and moves into the verification step', async () => {
        authApiMocks.requestOtp.mockResolvedValue({
            otp_request_id: '01K31JY2R4D9V9G4QJVNT8ET9X',
            expires_at: '2026-08-21T10:05:00Z',
            resend_available_at: '2026-08-21T10:01:00Z',
        });

        const wrapper = mount(LoginPage, {
            props: {
                canResetPassword: false,
            },
            global: {
                stubs: loginPageStubs,
            },
        });

        await wrapper.get('#mobile').setValue('+91 98765 43210');
        await wrapper.get('[data-test="request-otp-button"]').trigger('click');
        await flushPromises();

        expect(authApiMocks.requestOtp).toHaveBeenCalledWith({
            mobile: '+91 98765 43210',
        });
        expect(wrapper.text()).toContain('Verify your code');
        expect(wrapper.text()).toContain('Resend available in');
        wrapper.unmount();
    });

    it('persists the token session after a successful OTP verification', async () => {
        authApiMocks.requestOtp.mockResolvedValue({
            otp_request_id: '01K31JY2R4D9V9G4QJVNT8ET9X',
            expires_at: '2026-08-21T10:05:00Z',
            resend_available_at: '2026-08-21T10:00:30Z',
        });
        authApiMocks.verifyOtp.mockResolvedValue({
            access_token: 'persisted-token',
            token_type: 'Bearer',
        });
        authApiMocks.fetchCurrentUser.mockResolvedValue({
            id: 42,
            name: 'Asha Patel',
            email: null,
            mobile_number: '+919876543210',
            status: 'active',
        });

        const replaceMock = vi.fn();
        vi.stubGlobal('location', {
            ...window.location,
            replace: replaceMock,
        });

        const wrapper = mount(LoginPage, {
            props: {
                canResetPassword: false,
                intendedUrl: '/customer/profile',
            },
            global: {
                stubs: loginPageStubs,
            },
        });

        await wrapper.get('#mobile').setValue('+91 98765 43210');
        await wrapper.get('[data-test="request-otp-button"]').trigger('click');
        await flushPromises();
        await wrapper.get('[data-test="otp-input"]').setValue('123456');
        await wrapper.get('[data-test="verify-otp-button"]').trigger('click');
        await flushPromises();

        expect(authApiMocks.verifyOtp).toHaveBeenCalledWith({
            otp_request_id: '01K31JY2R4D9V9G4QJVNT8ET9X',
            code: '123456',
        }, {
            headers: {
                'X-Client-Mode': 'web',
            },
        });
        expect(
            browserSessionMocks.persistBrowserTokenSession,
        ).toHaveBeenCalledTimes(2);
        expect(
            browserSessionMocks.persistBrowserTokenSession,
        ).toHaveBeenLastCalledWith(
            expect.objectContaining({
                accessToken: 'persisted-token',
                persistence: 'local',
                auth: expect.objectContaining({
                    preferredSurface: 'customer',
                    roles: ['customer'],
                }),
            }),
        );
        expect(replaceMock).toHaveBeenCalledWith('/customer/profile');
        wrapper.unmount();
    });

    it('redirects authenticated visitors away from the login page', async () => {
        browserSessionMocks.getBrowserSessionState.mockReturnValue({
            hasRestored: true,
            isRestoring: false,
        });
        browserSessionMocks.resolveBrowserSessionAuth.mockReturnValue({
            user: {
                id: 42,
                name: 'Asha Patel',
                email: null,
                email_verified_at: null,
            },
            roles: ['customer'],
            permissions: [],
            preferredSurface: 'customer',
            sessionMode: 'token',
        });

        const replaceMock = vi.fn();
        vi.stubGlobal('location', {
            ...window.location,
            replace: replaceMock,
        });

        const wrapper = mount(LoginPage, {
            props: {
                canResetPassword: false,
                intendedUrl: '/customer/profile',
            },
            global: {
                stubs: loginPageStubs,
            },
        });

        await flushPromises();

        expect(browserSessionMocks.initializeBrowserSession).toHaveBeenCalled();
        expect(replaceMock).toHaveBeenCalledWith('/customer/profile');
        wrapper.unmount();
    });

    it('shows a calm blocked-account message when verification is forbidden', async () => {
        authApiMocks.requestOtp.mockResolvedValue({
            otp_request_id: '01K31JY2R4D9V9G4QJVNT8ET9X',
            expires_at: '2026-08-21T10:05:00Z',
            resend_available_at: '2026-08-21T10:00:30Z',
        });
        authApiMocks.verifyOtp.mockRejectedValue(
            new ApiClientError(403, new Headers(), {
                code: 'USER_BLOCKED',
                message: 'Blocked.',
            }),
        );

        const wrapper = mount(LoginPage, {
            props: {
                canResetPassword: false,
            },
            global: {
                stubs: loginPageStubs,
            },
        });

        await wrapper.get('#mobile').setValue('+91 98765 43210');
        await wrapper.get('[data-test="request-otp-button"]').trigger('click');
        await flushPromises();
        await wrapper.get('[data-test="otp-input"]').setValue('123456');
        await wrapper.get('[data-test="verify-otp-button"]').trigger('click');
        await flushPromises();

        expect(browserSessionMocks.clearBrowserSession).toHaveBeenCalledTimes(
            1,
        );
        expect(wrapper.text()).toContain(
            'This account is blocked. Please contact support for help.',
        );
        wrapper.unmount();
    });

    it('updates resend and expiry messaging as time advances', async () => {
        authApiMocks.requestOtp.mockResolvedValue({
            otp_request_id: '01K31JY2R4D9V9G4QJVNT8ET9X',
            expires_at: '2026-08-21T10:00:05Z',
            resend_available_at: '2026-08-21T10:00:02Z',
        });

        const wrapper = mount(LoginPage, {
            props: {
                canResetPassword: false,
            },
            global: {
                stubs: loginPageStubs,
            },
        });

        await wrapper.get('#mobile').setValue('+91 98765 43210');
        await wrapper.get('[data-test="request-otp-button"]').trigger('click');
        await flushPromises();

        expect(wrapper.text()).toContain('Resend available in');

        await vi.advanceTimersByTimeAsync(6000);
        await flushPromises();

        expect(wrapper.text()).toContain(
            'That code has expired. Request a fresh OTP to continue.',
        );
        expect(
            wrapper
                .get('[data-test="resend-otp-button"]')
                .attributes('disabled'),
        ).toBeUndefined();
        wrapper.unmount();
    });
});
