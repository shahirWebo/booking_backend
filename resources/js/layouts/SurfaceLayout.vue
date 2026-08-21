<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Smartphone } from '@lucide/vue';
import { computed, onMounted, watch } from 'vue';
import MobileAppBar from '@/components/mobile/MobileAppBar.vue';
import MobileBottomNav from '@/components/mobile/MobileBottomNav.vue';
import { Toaster } from '@/components/ui/sonner';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import {
    getBrowserSessionState,
    initializeBrowserSession,
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

const surfaceKey = computed(() =>
    getProductSurfaceKeyFromComponent(page.component),
);

const surface = computed(() =>
    surfaceKey.value ? findProductSurface(surfaceKey.value) : null,
);

const auth = computed(() => page.props.auth);
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
                </div>

                <div v-if="surface" class="mt-5 flex flex-col gap-4">
                    <MobileAppBar
                        :title="surface.title"
                        :subtitle="surface.summary"
                        :eyebrow="surface.audience"
                        leading-href="/"
                        leading-label="All surfaces"
                    >
                        <template #actions>
                            <div
                                class="inline-flex min-h-[2rem] w-fit items-center gap-2 rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold tracking-[0.24em] text-sky-900 uppercase"
                            >
                                <Smartphone class="h-4 w-4" />
                                Native-like shell
                            </div>
                        </template>
                    </MobileAppBar>

                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div class="app-panel-muted text-sm text-slate-600">
                            <p class="font-semibold text-slate-900">
                                Session continuity
                            </p>
                            <p class="mt-1 leading-6">
                                {{ sessionLabel }}
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-2 overflow-x-auto pb-1">
                        <Link
                            v-for="item in bottomNavigation"
                            :key="item.key"
                            :href="item.href"
                            class="app-chip shrink-0 transition"
                            :class="
                                isCurrentUrl(item.href)
                                    ? 'bg-slate-950 text-white shadow-sm'
                                    : 'border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50'
                            "
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </div>
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
