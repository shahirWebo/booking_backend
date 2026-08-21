export type ProductSurfaceKey = 'customer' | 'vendor' | 'admin';

export type ProductModule = {
    key: string;
    title: string;
    description: string;
    routePrefix: string;
};

export type ProductSurface = {
    key: ProductSurfaceKey;
    title: string;
    audience: string;
    href: string;
    summary: string;
    deliveryNote: string;
    modules: ProductModule[];
};

export const productSurfaces: ProductSurface[] = [
    {
        key: 'customer',
        title: 'Customer App',
        audience: 'Players and booking customers',
        href: '/customer',
        summary:
            'Discovery, booking, payments, profile, favorites, support, and review flows for the mobile-first customer journey.',
        deliveryNote:
            'Optimized first for touch discovery and checkout flows, then adapted for wider screens and WebView reuse.',
        modules: [
            {
                key: 'auth',
                title: 'Authentication',
                description: 'OTP entry, verification, and session restore.',
                routePrefix: '/customer/auth',
            },
            {
                key: 'search',
                title: 'Discovery',
                description:
                    'Home, nearby listings, filters, turf details, dates, and slot selection.',
                routePrefix: '/customer/search',
            },
            {
                key: 'bookings',
                title: 'Bookings',
                description:
                    'Booking review, payment handoff, upcoming bookings, history, and details.',
                routePrefix: '/customer/bookings',
            },
            {
                key: 'account',
                title: 'Account',
                description:
                    'Profile, notification preferences, favorites, reviews, and support settings.',
                routePrefix: '/customer/account',
            },
        ],
    },
    {
        key: 'vendor',
        title: 'Vendor Portal',
        audience: 'Venue owners and staff',
        href: '/vendor',
        summary:
            'Onboarding, locations, turfs, availability, pricing, bookings, staff, calendar, and finance workflows.',
        deliveryNote:
            'Structured around operational modules that later plug into native-like vendor navigation and role-aware access.',
        modules: [
            {
                key: 'auth',
                title: 'Authentication',
                description:
                    'Vendor OTP access and authenticated session handling.',
                routePrefix: '/vendor/auth',
            },
            {
                key: 'onboarding',
                title: 'Onboarding',
                description:
                    'Business details, KYC, bank setup, review, approval, and resubmission.',
                routePrefix: '/vendor/onboarding',
            },
            {
                key: 'operations',
                title: 'Operations',
                description:
                    'Locations, turfs, availability, pricing, and daily booking operations.',
                routePrefix: '/vendor/operations',
            },
            {
                key: 'team-and-finance',
                title: 'Team and Finance',
                description:
                    'Staff management, vendor calendar, earnings, settlements, and support.',
                routePrefix: '/vendor/workspace',
            },
        ],
    },
    {
        key: 'admin',
        title: 'Admin Console',
        audience: 'Platform operators',
        href: '/admin',
        summary:
            'Secure operational surface for oversight of vendors, commerce, support, coupons, settings, and audit activity.',
        deliveryNote:
            'Keeps sensitive operational modules separated from customer and vendor flows while remaining mobile-usable where needed.',
        modules: [
            {
                key: 'auth',
                title: 'Authentication and RBAC',
                description:
                    'Secure admin sign-in, role checks, and permission-scoped entry points.',
                routePrefix: '/admin/auth',
            },
            {
                key: 'marketplace',
                title: 'Marketplace Operations',
                description:
                    'Vendors, locations, turfs, sports, amenities, reviews, and bookings.',
                routePrefix: '/admin/operations',
            },
            {
                key: 'finance',
                title: 'Finance and Risk',
                description:
                    'Payments, refunds, commissions, settlements, and financial controls.',
                routePrefix: '/admin/finance',
            },
            {
                key: 'support-and-governance',
                title: 'Support and Governance',
                description:
                    'Support queues, notifications, system settings, and audit logs.',
                routePrefix: '/admin/governance',
            },
        ],
    },
];

export const findProductSurface = (key: ProductSurfaceKey): ProductSurface => {
    const surface = productSurfaces.find((candidate) => candidate.key === key);

    if (!surface) {
        throw new Error(`Unknown product surface: ${key}`);
    }

    return surface;
};

export const getProductSurfaceKeyFromComponent = (
    component: string,
): ProductSurfaceKey | null => {
    if (component.startsWith('customer/')) {
        return 'customer';
    }

    if (component.startsWith('vendor/')) {
        return 'vendor';
    }

    if (component.startsWith('admin/')) {
        return 'admin';
    }

    return null;
};
