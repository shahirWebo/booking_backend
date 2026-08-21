import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiClientError } from '@/lib/api/client';
import type { Auth } from '@/types/auth';

const authApiMocks = vi.hoisted(() => ({
    fetchCurrentUser: vi.fn(),
    logout: vi.fn(),
}));

vi.mock('@/lib/api/auth', () => ({
    authApiService: authApiMocks,
}));

const guestAuth: Auth = {
    user: null,
    roles: [],
    permissions: [],
    preferredSurface: null,
    sessionMode: 'guest',
};

const vendorAuth: Auth = {
    user: {
        id: 7,
        name: 'Venue Owner',
        email: 'owner@example.com',
        email_verified_at: null,
        created_at: '2026-08-21T00:00:00Z',
        updated_at: '2026-08-21T00:00:00Z',
    },
    roles: ['vendor_owner'],
    permissions: ['view_vendor_finance'],
    preferredSurface: 'vendor',
    sessionMode: 'token',
};

describe('browserSession', () => {
    beforeEach(() => {
        authApiMocks.fetchCurrentUser.mockReset();
        authApiMocks.logout.mockReset();
    });

    it('persists token sessions in local storage for restore-on-reopen flows', async () => {
        vi.resetModules();

        const browserSession = await import('@/lib/browserSession');

        browserSession.initializeBrowserSession(guestAuth);
        browserSession.persistBrowserTokenSession({
            accessToken: 'persisted-token',
            auth: vendorAuth,
            persistence: 'local',
        });

        expect(browserSession.getBrowserSessionState().accessToken).toBe(
            'persisted-token',
        );
        expect(window.localStorage.length).toBe(1);
    });

    it('hydrates a previously persisted session on the next app boot', async () => {
        vi.resetModules();

        const firstBrowserSession = await import('@/lib/browserSession');

        firstBrowserSession.initializeBrowserSession(guestAuth);
        firstBrowserSession.persistBrowserTokenSession({
            accessToken: 'persisted-token',
            auth: vendorAuth,
            persistence: 'local',
        });

        vi.resetModules();

        const restoredBrowserSession = await import('@/lib/browserSession');

        restoredBrowserSession.initializeBrowserSession(guestAuth);

        expect(
            restoredBrowserSession.getBrowserSessionState().accessToken,
        ).toBe('persisted-token');
        expect(
            restoredBrowserSession.getBrowserSessionState().preferredSurface,
        ).toBe('vendor');
    });

    it('revalidates and restores the persisted user on the next app boot', async () => {
        vi.resetModules();

        const firstBrowserSession = await import('@/lib/browserSession');

        firstBrowserSession.initializeBrowserSession(guestAuth);
        firstBrowserSession.persistBrowserTokenSession({
            accessToken: 'persisted-token',
            auth: {
                ...vendorAuth,
                user: null,
            },
            persistence: 'local',
        });

        vi.resetModules();
        authApiMocks.fetchCurrentUser.mockResolvedValue({
            id: 7,
            name: 'Venue Owner',
            email: 'owner@example.com',
            mobile_number: '+919876543210',
            status: 'active',
        });

        const restoredBrowserSession = await import('@/lib/browserSession');

        restoredBrowserSession.initializeBrowserSession(guestAuth);
        await flushPromises();

        expect(authApiMocks.fetchCurrentUser).toHaveBeenCalledTimes(1);
        expect(restoredBrowserSession.getBrowserSessionState().user).toEqual(
            expect.objectContaining({
                id: 7,
                name: 'Venue Owner',
                mobile_number: '+919876543210',
            }),
        );
        expect(
            restoredBrowserSession.getBrowserSessionState().hasRestored,
        ).toBe(true);
    });

    it('clears persisted state when restore receives an unauthenticated response', async () => {
        vi.resetModules();

        const firstBrowserSession = await import('@/lib/browserSession');

        firstBrowserSession.initializeBrowserSession(guestAuth);
        firstBrowserSession.persistBrowserTokenSession({
            accessToken: 'persisted-token',
            auth: {
                ...vendorAuth,
                user: null,
            },
            persistence: 'local',
        });

        vi.resetModules();
        authApiMocks.fetchCurrentUser.mockRejectedValue(
            new ApiClientError(401, new Headers(), {
                code: 'UNAUTHENTICATED',
                message: 'Unauthenticated.',
            }),
        );

        const restoredBrowserSession = await import('@/lib/browserSession');

        restoredBrowserSession.initializeBrowserSession(guestAuth);
        await flushPromises();

        expect(
            restoredBrowserSession.getBrowserSessionState().accessToken,
        ).toBe(null);
        expect(restoredBrowserSession.getBrowserSessionState().user).toBeNull();
        expect(window.localStorage.length).toBe(0);
    });

    it('exposes restored token sessions as effective auth for guest page props', async () => {
        vi.resetModules();

        const browserSession = await import('@/lib/browserSession');

        browserSession.initializeBrowserSession(guestAuth);
        browserSession.persistBrowserTokenSession({
            accessToken: 'persisted-token',
            auth: vendorAuth,
            persistence: 'local',
        });

        const effectiveAuth =
            browserSession.resolveBrowserSessionAuth(guestAuth);

        expect(effectiveAuth.sessionMode).toBe('token');
        expect(effectiveAuth.user).toEqual(vendorAuth.user);
        expect(effectiveAuth.roles).toEqual(['vendor_owner']);
        expect(effectiveAuth.preferredSurface).toBe('vendor');
    });

    it('revokes the current token session and clears persisted browser state', async () => {
        vi.resetModules();
        authApiMocks.logout.mockResolvedValue(undefined);

        const browserSession = await import('@/lib/browserSession');

        browserSession.initializeBrowserSession(guestAuth);
        browserSession.persistBrowserTokenSession({
            accessToken: 'persisted-token',
            auth: vendorAuth,
            persistence: 'local',
        });

        await browserSession.logoutBrowserSession();

        expect(authApiMocks.logout).toHaveBeenCalledTimes(1);
        expect(browserSession.getBrowserSessionState().accessToken).toBeNull();
        expect(window.localStorage.length).toBe(0);
    });

    it('still clears persisted browser state when remote logout fails', async () => {
        vi.resetModules();
        authApiMocks.logout.mockRejectedValue(new Error('offline'));

        const browserSession = await import('@/lib/browserSession');

        browserSession.initializeBrowserSession(guestAuth);
        browserSession.persistBrowserTokenSession({
            accessToken: 'persisted-token',
            auth: vendorAuth,
            persistence: 'local',
        });

        await expect(
            browserSession.logoutBrowserSession(),
        ).resolves.toBeUndefined();
        expect(browserSession.getBrowserSessionState().accessToken).toBeNull();
        expect(window.localStorage.length).toBe(0);
    });
});
