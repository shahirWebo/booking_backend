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

const adminOperationsAuth: Auth = {
    user: {
        id: 2,
        name: 'Admin Ops',
        email: 'admin-ops@example.com',
        email_verified_at: null,
        created_at: '2026-08-21T00:00:00Z',
        updated_at: '2026-08-21T00:00:00Z',
    },
    roles: ['admin_operations'],
    permissions: ['manage_sports'],
    preferredSurface: 'admin',
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

    it('shows sports navigation to admin operators with sport-management access', () => {
        const items = getSurfaceBottomNavigation('admin', adminOperationsAuth);

        expect(items.map((item) => item.key)).toContain('admin-sports');
    });

    it('shows amenities navigation to admin operators with amenity-management access', () => {
        const items = getSurfaceBottomNavigation('admin', {
            ...adminOperationsAuth,
            permissions: ['manage_amenities'],
        });

        expect(items.map((item) => item.key)).toContain('admin-amenities');
    });
});
