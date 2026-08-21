import {
    CircleUserRound,
    CalendarRange,
    CircleDollarSign,
    Compass,
    Cog,
    Headphones,
    Home,
    MapPinned,
    ShieldCheck,
    Trophy,
    Users,
} from '@lucide/vue';
import type { Auth } from '@/types/auth';
import type {
    SurfaceNavItem,
    SurfaceNavSection,
    SurfaceNavigationMap,
} from '@/types/navigation';

const surfaceNavigation: SurfaceNavigationMap = {
    customer: [
        {
            key: 'customer-primary',
            title: 'Explore',
            items: [
                {
                    key: 'customer-home',
                    title: 'Home',
                    mobileLabel: 'Home',
                    href: '/customer',
                    icon: Home,
                    matchPrefixes: ['/customer'],
                    summary:
                        'Browse nearby turfs, offers, and saved discovery filters.',
                },
                {
                    key: 'customer-search',
                    title: 'Find turfs',
                    mobileLabel: 'Search',
                    href: '/customer/search',
                    icon: Compass,
                    matchPrefixes: ['/customer/search'],
                    summary:
                        'Jump into location, date, and sport-based search flows.',
                },
                {
                    key: 'customer-bookings',
                    title: 'My bookings',
                    mobileLabel: 'Bookings',
                    href: '/customer/bookings',
                    icon: CalendarRange,
                    requiresAuth: true,
                    matchPrefixes: ['/customer/bookings'],
                    summary:
                        'Track upcoming bookings, cancellations, and reschedules.',
                },
                {
                    key: 'customer-support',
                    title: 'Support',
                    mobileLabel: 'Support',
                    href: '/customer/support',
                    icon: Headphones,
                    matchPrefixes: ['/customer/support'],
                    summary:
                        'Open help, chat, and booking-assistance journeys.',
                },
            ],
        },
        {
            key: 'customer-account',
            title: 'Account',
            items: [
                {
                    key: 'customer-profile',
                    title: 'Profile',
                    mobileLabel: 'Profile',
                    href: '/customer/profile',
                    icon: CircleUserRound,
                    requiresAuth: true,
                    matchPrefixes: ['/customer/profile'],
                    summary:
                        'Review your player identity, contact details, and account basics.',
                },
            ],
        },
    ],
    vendor: [
        {
            key: 'vendor-operations',
            title: 'Operations',
            items: [
                {
                    key: 'vendor-home',
                    title: 'Operations hub',
                    mobileLabel: 'Hub',
                    href: '/vendor',
                    icon: Home,
                    matchPrefixes: ['/vendor'],
                    summary:
                        'Start from today’s bookings, availability, and venue alerts.',
                },
                {
                    key: 'vendor-locations',
                    title: 'Locations',
                    mobileLabel: 'Locations',
                    href: '/vendor/operations/locations',
                    icon: MapPinned,
                    requiresAuth: true,
                    rolesAny: [
                        'vendor_owner',
                        'vendor_manager',
                        'vendor_staff',
                    ],
                    matchPrefixes: ['/vendor/operations/locations'],
                    summary:
                        'Manage venue details, turf metadata, and publishing state.',
                },
                {
                    key: 'vendor-calendar',
                    title: 'Availability',
                    mobileLabel: 'Calendar',
                    href: '/vendor/operations/availability',
                    icon: CalendarRange,
                    requiresAuth: true,
                    rolesAny: [
                        'vendor_owner',
                        'vendor_manager',
                        'vendor_staff',
                    ],
                    matchPrefixes: ['/vendor/operations/availability'],
                    summary:
                        'Edit slot availability, closures, and booking exceptions.',
                },
                {
                    key: 'vendor-finance',
                    title: 'Finance',
                    mobileLabel: 'Finance',
                    href: '/vendor/workspace/finance',
                    icon: CircleDollarSign,
                    requiresAuth: true,
                    rolesAny: ['vendor_owner', 'vendor_accountant'],
                    permissionsAny: ['view_vendor_finance'],
                    matchPrefixes: ['/vendor/workspace/finance'],
                    summary:
                        'Review payouts, settlements, and payment discrepancies.',
                },
            ],
        },
    ],
    admin: [
        {
            key: 'admin-operations',
            title: 'Platform controls',
            items: [
                {
                    key: 'admin-home',
                    title: 'Admin home',
                    mobileLabel: 'Home',
                    href: '/admin',
                    icon: ShieldCheck,
                    matchPrefixes: ['/admin'],
                    summary:
                        'Watch approvals, escalations, and platform health at a glance.',
                },
                {
                    key: 'admin-marketplace',
                    title: 'Marketplace',
                    mobileLabel: 'Market',
                    href: '/admin/operations/vendors',
                    icon: MapPinned,
                    requiresAuth: true,
                    rolesAny: ['super_admin', 'admin_ops', 'admin_support'],
                    matchPrefixes: ['/admin/operations'],
                    summary:
                        'Review vendors, locations, turfs, and booking anomalies.',
                },
                {
                    key: 'admin-sports',
                    title: 'Sports',
                    mobileLabel: 'Sports',
                    href: '/admin/operations/sports',
                    icon: Trophy,
                    requiresAuth: true,
                    rolesAny: ['super_admin', 'admin_operations'],
                    permissionsAny: ['manage_sports'],
                    matchPrefixes: ['/admin/operations/sports'],
                    summary:
                        'Manage the shared sports catalog used across discovery and vendor operations.',
                },
                {
                    key: 'admin-amenities',
                    title: 'Amenities',
                    mobileLabel: 'Amenities',
                    href: '/admin/operations/amenities',
                    icon: MapPinned,
                    requiresAuth: true,
                    rolesAny: ['super_admin', 'admin_operations'],
                    permissionsAny: ['manage_amenities'],
                    matchPrefixes: ['/admin/operations/amenities'],
                    summary:
                        'Manage the shared amenities catalog used across venue and turf operations.',
                },
                {
                    key: 'admin-finance',
                    title: 'Finance',
                    mobileLabel: 'Finance',
                    href: '/admin/finance',
                    icon: CircleDollarSign,
                    requiresAuth: true,
                    rolesAny: ['super_admin', 'admin_finance'],
                    permissionsAny: ['view_platform_finance'],
                    matchPrefixes: ['/admin/finance'],
                    summary:
                        'Investigate payments, refunds, and settlement reconciliation.',
                },
                {
                    key: 'admin-access',
                    title: 'Access',
                    mobileLabel: 'Access',
                    href: '/admin/governance/access',
                    icon: Users,
                    requiresAuth: true,
                    rolesAny: ['super_admin', 'admin_support'],
                    permissionsAny: ['manage_roles_and_permissions'],
                    matchPrefixes: ['/admin/governance'],
                    summary:
                        'Control admin roles, permissions, and audit-sensitive workflows.',
                },
                {
                    key: 'admin-system-settings',
                    title: 'Settings',
                    mobileLabel: 'Settings',
                    href: '/admin/governance/system-settings',
                    icon: Cog,
                    requiresAuth: true,
                    rolesAny: ['super_admin'],
                    permissionsAny: ['manage_system_settings'],
                    matchPrefixes: ['/admin/governance/system-settings'],
                    summary:
                        'Maintain protected platform-wide booking, OTP, and support configuration.',
                },
            ],
        },
    ],
};

export function getSurfaceNavigation(
    surfaceKey: keyof SurfaceNavigationMap,
    auth: Auth,
): SurfaceNavSection[] {
    return surfaceNavigation[surfaceKey].map((section) => ({
        ...section,
        items: section.items.filter((item) =>
            canAccessNavigationItem(item, auth),
        ),
    }));
}

export function getSurfaceBottomNavigation(
    surfaceKey: keyof SurfaceNavigationMap,
    auth: Auth,
): SurfaceNavItem[] {
    return getSurfaceNavigation(surfaceKey, auth)
        .flatMap((section) => section.items)
        .slice(0, 4);
}

function canAccessNavigationItem(item: SurfaceNavItem, auth: Auth): boolean {
    if (item.requiresAuth && !auth.user) {
        return false;
    }

    if (
        item.rolesAny?.length &&
        !item.rolesAny.some((role) => auth.roles.includes(role))
    ) {
        return false;
    }

    if (
        item.permissionsAny?.length &&
        !item.permissionsAny.some((permission) =>
            auth.permissions.includes(permission),
        )
    ) {
        return false;
    }

    return true;
}
