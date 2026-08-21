<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, ShieldCheck, Store, Trophy } from '@lucide/vue';
import { computed } from 'vue';
import FeedbackFoundationPreview from '@/components/feedback/FeedbackFoundationPreview.vue';
import MobileFoundationPreview from '@/components/mobile/MobileFoundationPreview.vue';
import { productSurfaces } from '@/lib/productSurfaces';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

const surfaceIcons = {
    customer: Trophy,
    vendor: Store,
    admin: ShieldCheck,
} as const;
</script>

<template>
    <Head title="Platform Surfaces" />

    <div
        class="app-screen min-h-screen bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.18),_transparent_32%),linear-gradient(180deg,_#f8fafc_0%,_#dbeafe_45%,_#eff6ff_100%)] text-slate-950"
    >
        <div class="app-shell">
            <section
                class="app-panel overflow-hidden bg-slate-950 text-slate-50"
            >
                <div
                    class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="max-w-3xl space-y-3">
                        <p class="app-eyebrow text-sky-300">
                            WEB-004 foundation
                        </p>
                        <h1 class="app-title">
                            Customer, vendor, and admin now have explicit web
                            surface boundaries.
                        </h1>
                        <p
                            class="max-w-2xl text-sm leading-6 text-slate-300 sm:text-base"
                        >
                            The frontend is no longer a generic starter shell.
                            It now has product-specific entry points and module
                            groupings that future mobile-first screens can grow
                            into without mixing audiences or workflows.
                        </p>
                    </div>

                    <Link
                        :href="user ? '/dashboard' : '/login'"
                        class="app-chip min-w-[13rem] justify-center bg-sky-400 text-slate-950 transition hover:bg-sky-300"
                    >
                        {{
                            user
                                ? 'Open workspace hub'
                                : 'Open current auth flow'
                        }}
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <article
                    v-for="surface in productSurfaces"
                    :key="surface.key"
                    class="app-panel flex h-full"
                >
                    <div class="flex items-start justify-between gap-3">
                        <component
                            :is="surfaceIcons[surface.key]"
                            class="h-6 w-6 text-sky-600"
                        />
                        <span
                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500"
                        >
                            {{ surface.modules.length }} modules
                        </span>
                    </div>

                    <div class="mt-5 space-y-3">
                        <p class="app-eyebrow">
                            {{ surface.audience }}
                        </p>
                        <h2 class="app-heading text-slate-950">
                            {{ surface.title }}
                        </h2>
                        <p class="app-copy-sm">
                            {{ surface.summary }}
                        </p>
                    </div>

                    <div class="mt-5 space-y-2">
                        <p
                            v-for="module in surface.modules"
                            :key="module.key"
                            class="rounded-2xl bg-slate-50 px-3 py-2 text-sm text-slate-600"
                        >
                            <span class="font-semibold text-slate-900">
                                {{ module.title }}:
                            </span>
                            {{ module.description }}
                        </p>
                    </div>

                    <Link
                        :href="surface.href"
                        class="app-chip mt-5 justify-between border border-slate-200 text-slate-700 transition hover:bg-slate-50"
                    >
                        View surface map
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </article>
            </section>

            <MobileFoundationPreview />
            <FeedbackFoundationPreview />
        </div>
    </div>
</template>
