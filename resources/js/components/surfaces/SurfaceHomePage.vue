<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, RotateCcw } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    getBrowserSessionState,
    resolveBrowserSessionAuth,
} from '@/lib/browserSession';
import { findProductSurface } from '@/lib/productSurfaces';
import type { ProductSurfaceKey } from '@/lib/productSurfaces';
import { getSurfaceNavigation } from '@/lib/surfaceNavigation';

const props = defineProps<{
    surfaceKey: ProductSurfaceKey;
}>();

const page = usePage();
const browserSession = getBrowserSessionState();
const auth = computed(() => resolveBrowserSessionAuth(page.props.auth));

const surface = computed(() => findProductSurface(props.surfaceKey));
const navigationSections = computed(() =>
    getSurfaceNavigation(props.surfaceKey, auth.value),
);
const browserSessionTimestamp = computed(() =>
    browserSession.lastRestoredAt
        ? new Date(browserSession.lastRestoredAt).toLocaleString()
        : null,
);
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
        <section class="app-panel overflow-hidden">
            <div class="app-stack-sm">
                <p class="app-eyebrow">Surface summary</p>
                <h2 class="app-heading">
                    {{ surface.summary }}
                </h2>
                <p class="app-copy max-w-3xl">
                    {{ surface.deliveryNote }}
                </p>
            </div>

            <div class="mt-5 grid gap-3">
                <article
                    v-for="module in surface.modules"
                    :key="module.key"
                    class="app-panel-muted"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">
                                {{ module.title }}
                            </h3>
                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                {{ module.description }}
                            </p>
                        </div>

                        <span
                            class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 shadow-sm"
                        >
                            {{ module.routePrefix }}
                        </span>
                    </div>
                </article>
            </div>
        </section>

        <aside class="space-y-4">
            <section
                v-for="section in navigationSections"
                :key="section.key"
                class="app-panel"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="app-eyebrow">
                            {{ section.title }}
                        </p>
                        <p class="app-copy-sm mt-1">
                            Role-aware entry points for the mobile shell.
                        </p>
                    </div>
                    <span class="text-sm text-slate-500">
                        {{ section.items.length }} items
                    </span>
                </div>

                <div class="mt-4 grid gap-3">
                    <Link
                        v-for="item in section.items"
                        :key="item.key"
                        :href="item.href"
                        class="app-interactive-card rounded-3xl border border-slate-200 bg-slate-50 shadow-sm transition hover:bg-white"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p
                                    class="text-base font-semibold text-slate-900"
                                >
                                    {{ item.title }}
                                </p>
                                <p
                                    class="mt-1 text-sm leading-6 text-slate-600"
                                >
                                    {{ item.summary }}
                                </p>
                            </div>
                            <ArrowRight class="mt-1 h-4 w-4 text-slate-400" />
                        </div>
                    </Link>
                </div>
            </section>

            <section
                class="app-panel border-slate-200 bg-slate-950 text-slate-50"
            >
                <p class="app-eyebrow text-sky-300">Session restore</p>
                <p class="mt-3 text-sm leading-6 text-slate-200">
                    API token continuity now has a shared browser store. Later
                    OTP and mobile flows can persist the access token, reopen
                    into the last signed-in surface, and keep the same client
                    boundary for WebView use.
                </p>

                <div
                    class="mt-4 rounded-3xl bg-white/10 px-4 py-3 text-sm leading-6 text-slate-100"
                >
                    <p class="font-semibold">Current state</p>
                    <p class="mt-1">
                        {{
                            browserSession.accessToken
                                ? 'An API session is available for automatic restore.'
                                : 'No API token has been persisted yet.'
                        }}
                    </p>
                    <p v-if="browserSessionTimestamp" class="mt-1 text-sky-100">
                        Last restored: {{ browserSessionTimestamp }}
                    </p>
                </div>

                <Button
                    type="button"
                    variant="secondary"
                    size="default"
                    class="mt-4"
                    disabled
                >
                    <RotateCcw class="h-4 w-4" />
                    Restore hook ready
                </Button>
            </section>
        </aside>
    </div>
</template>
