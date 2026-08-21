<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Smartphone } from '@lucide/vue';
import { computed, onMounted, watch } from 'vue';
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
</script>

<template>
    <Head :title="surface?.title ?? page.props.name" />

    <div
        class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.18),_transparent_36%),linear-gradient(180deg,_#f8fafc_0%,_#dbeafe_52%,_#f8fafc_100%)] text-slate-950"
    >
        <div
            class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 pt-5 pb-28 sm:px-6 sm:pb-10"
        >
            <header
                class="sticky top-0 z-20 -mx-4 mb-5 border-b border-white/70 bg-white/85 px-4 pt-3 pb-4 backdrop-blur sm:static sm:mx-0 sm:rounded-[2rem] sm:border sm:px-5 sm:pt-5"
            >
                <div class="flex items-center justify-between gap-3">
                    <Link
                        href="/"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        All surfaces
                    </Link>

                    <Link
                        :href="entryHref"
                        class="inline-flex items-center gap-2 rounded-full bg-slate-950 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800"
                    >
                        {{ entryLabel }}
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>

                <div v-if="surface" class="mt-5 flex flex-col gap-4">
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div class="space-y-2">
                            <div
                                class="inline-flex w-fit items-center gap-2 rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold tracking-[0.24em] text-sky-900 uppercase"
                            >
                                <Smartphone class="h-4 w-4" />
                                Native-like shell
                            </div>
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-[0.24em] text-slate-500 uppercase"
                                >
                                    {{ surface.audience }}
                                </p>
                                <h1
                                    class="text-3xl font-semibold tracking-tight sm:text-4xl"
                                >
                                    {{ surface.title }}
                                </h1>
                            </div>
                        </div>

                        <div
                            class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 shadow-sm"
                        >
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
                            class="inline-flex shrink-0 items-center gap-2 rounded-full px-3 py-2 text-sm font-medium transition"
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
                class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-18px_50px_-35px_rgba(15,23,42,0.6)] backdrop-blur sm:hidden"
            >
                <div class="mx-auto grid max-w-md grid-cols-4 gap-2">
                    <Link
                        v-for="item in bottomNavigation"
                        :key="`${item.key}-mobile`"
                        :href="item.href"
                        class="flex flex-col items-center gap-1 rounded-2xl px-2 py-2 text-[11px] font-medium transition"
                        :class="
                            isCurrentUrl(item.href)
                                ? 'bg-slate-950 text-white'
                                : 'text-slate-500 hover:bg-slate-100'
                        "
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                        {{ item.mobileLabel ?? item.title }}
                    </Link>
                </div>
            </nav>
        </div>

        <Toaster close-button rich-colors />
    </div>
</template>
