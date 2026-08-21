<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, LogOut, Smartphone } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import MobileAppBar from '@/components/mobile/MobileAppBar.vue';
import MobileBottomNav from '@/components/mobile/MobileBottomNav.vue';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { Spinner } from '@/components/ui/spinner';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import {
    getBrowserSessionState,
    initializeBrowserSession,
    logoutBrowserSession,
    resolveBrowserSessionAuth,
    syncBrowserSessionFromPage,
} from '@/lib/browserSession';
import {
    findProductSurface,
    getProductSurfaceKeyFromComponent,
} from '@/lib/productSurfaces';
import { getSurfaceBottomNavigation } from '@/lib/surfaceNavigation';

const page = usePage();
const browserSession = getBrowserSessionState();
const { isCurrentUrl } = useCurrentUrl();
const isLoggingOut = ref(false);

const surfaceKey = computed(() =>
    getProductSurfaceKeyFromComponent(page.component),
);

const surface = computed(() =>
    surfaceKey.value ? findProductSurface(surfaceKey.value) : null,
);

const auth = computed(() => resolveBrowserSessionAuth(page.props.auth));
const bottomNavigation = computed(() =>
    surfaceKey.value
        ? getSurfaceBottomNavigation(surfaceKey.value, auth.value)
        : [],
);
const entryHref = computed(() => {
    if (auth.value.user) {
        return auth.value.preferredSurface
            ? `/${auth.value.preferredSurface}`
            : '/dashboard';
    }

    return '/login';
});

const entryLabel = computed(() =>
    auth.value.user ? 'Continue workspace' : 'Open auth flow',
);
const sessionLabel = computed(() => {
    if (!browserSession.accessToken) {
        return 'No persisted API session yet';
    }

    return browserSession.persistence === 'session'
        ? 'Restores until the tab is closed'
        : 'Restores across refresh and reopen';
});
const canLogout = computed(
    () => auth.value.sessionMode === 'token' && auth.value.user !== null,
);

watch(
    auth,
    (nextAuth) => {
        syncBrowserSessionFromPage(nextAuth);
    },
    { deep: true, immediate: true },
);

onMounted(() => {
    initializeBrowserSession(auth.value);
});

const logoutHref = '/login';

async function logoutFromSurface(): Promise<void> {
    if (isLoggingOut.value) {
        return;
    }

    isLoggingOut.value = true;

    try {
        await logoutBrowserSession();
    } finally {
        window.location.assign(logoutHref);
    }
}

const mobileNavItems = computed(() =>
    bottomNavigation.value.map((item) => ({
        active: isCurrentUrl(item.href),
        href: item.href,
        icon: item.icon,
        key: item.key,
        label: item.mobileLabel ?? item.title,
    })),
);
</script>

<template>
    <Head :title="surface?.title ?? page.props.name" />

    <div
        class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.18),_transparent_36%),linear-gradient(180deg,_#f8fafc_0%,_#dbeafe_52%,_#f8fafc_100%)] text-slate-950"
    >
        <div class="app-shell app-screen pb-28 sm:pb-10">
            <header
                class="sticky top-0 z-20 -mx-[var(--container-padding-mobile)] mb-5 border-b border-white/70 bg-white/85 px-[var(--container-padding-mobile)] pt-3 pb-4 backdrop-blur sm:static sm:mx-0 sm:rounded-[var(--radius-surface)] sm:border sm:px-5 sm:pt-5"
            >
                <div class="flex items-start justify-between gap-3">
                    <Link
                        :href="entryHref"
                        class="app-chip bg-slate-950 text-white shadow-sm transition hover:bg-slate-800"
                    >
                        {{ entryLabel }}
                        <ArrowRight class="h-4 w-4" />
                    </Link>

                    <Button
                        v-if="canLogout"
                        type="button"
                        variant="outline"
                        size="sm"
                        class="rounded-full border-slate-200 bg-white/90 text-slate-700 shadow-sm hover:bg-white"
                        :disabled="isLoggingOut"
                        data-test="surface-logout-button"
                        @click="logoutFromSurface"
                    >
                        <Spinner v-if="isLoggingOut" />
                        <LogOut v-else class="h-4 w-4" />
                        Sign out
                    </Button>
                </div>

                
            </header>

            <main class="flex-1">
                <slot />
            </main>

            <nav
                v-if="bottomNavigation.length"
                class="fixed inset-x-0 bottom-0 z-30 sm:hidden"
            >
                <MobileBottomNav :items="mobileNavItems" />
            </nav>
        </div>

        <Toaster close-button rich-colors />
    </div>
</template>
