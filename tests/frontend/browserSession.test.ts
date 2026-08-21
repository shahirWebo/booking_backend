import { describe, expect, it, vi } from 'vitest';
import type { Auth } from '@/types/auth';

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

        expect(restoredBrowserSession.getBrowserSessionState().accessToken).toBe(
            'persisted-token',
        );
        expect(
            restoredBrowserSession.getBrowserSessionState().preferredSurface,
        ).toBe('vendor');
    });
});
