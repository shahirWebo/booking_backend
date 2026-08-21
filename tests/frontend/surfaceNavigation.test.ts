import { describe, expect, it } from 'vitest';
import { getSurfaceBottomNavigation } from '@/lib/surfaceNavigation';
import type { Auth } from '@/types/auth';

const guestAuth: Auth = {
    user: null,
    roles: [],
    permissions: [],
    preferredSurface: null,
    sessionMode: 'guest',
};

const vendorOwnerAuth: Auth = {
    user: {
        id: 1,
        name: 'Owner',
        email: 'owner@example.com',
        email_verified_at: null,
        created_at: '2026-08-21T00:00:00Z',
        updated_at: '2026-08-21T00:00:00Z',
    },
    roles: ['vendor_owner'],
    permissions: ['view_vendor_finance'],
    preferredSurface: 'vendor',
    sessionMode: 'cookie',
};

describe('surfaceNavigation', () => {
    it('hides authenticated vendor tabs from guests', () => {
        const items = getSurfaceBottomNavigation('vendor', guestAuth);

        expect(items.map((item) => item.key)).toEqual(['vendor-home']);
    });

    it('shows finance navigation to vendor owners with permission access', () => {
        const items = getSurfaceBottomNavigation('vendor', vendorOwnerAuth);

        expect(items.map((item) => item.key)).toContain('vendor-finance');
    });
});
