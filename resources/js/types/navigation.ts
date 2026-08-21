import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
import type { ProductSurfaceKey } from '@/lib/productSurfaces';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
};

export type SurfaceNavItem = NavItem & {
    key: string;
    summary: string;
    mobileLabel?: string;
    matchPrefixes?: string[];
    requiresAuth?: boolean;
    rolesAny?: string[];
    permissionsAny?: string[];
};

export type SurfaceNavSection = {
    key: string;
    title: string;
    items: SurfaceNavItem[];
};

export type SurfaceNavigationMap = Record<
    ProductSurfaceKey,
    SurfaceNavSection[]
>;
