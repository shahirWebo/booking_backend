<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Smartphone } from '@lucide/vue';
import { computed } from 'vue';
import type { ProductSurface } from '@/lib/productSurfaces';

defineProps<{
    surface: ProductSurface;
}>();

const page = usePage();
const workspaceHref = computed(() =>
    page.props.auth?.user ? '/dashboard' : '/login',
);
</script>

<template>
    <Head :title="surface.title" />

    <div
        class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.16),_transparent_38%),linear-gradient(180deg,_#f8fafc_0%,_#e2e8f0_100%)] px-4 py-6 text-slate-950 sm:px-6 lg:px-8"
    >
        <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
            <div
                class="overflow-hidden rounded-[2rem] border border-white/70 bg-white/85 shadow-[0_30px_80px_-40px_rgba(15,23,42,0.35)] backdrop-blur"
            >
                <div
                    class="flex flex-col gap-6 border-b border-slate-200/80 px-5 py-5 sm:px-7"
                >
                    <div class="flex items-center justify-between gap-3">
                        <Link
                            href="/"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            All surfaces
                        </Link>
                        <div
                            class="inline-flex items-center gap-2 rounded-full bg-sky-100 px-3 py-2 text-xs font-semibold tracking-[0.24em] text-sky-800 uppercase"
                        >
                            <Smartphone class="h-4 w-4" />
                            Mobile-first foundation
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <p
                            class="text-xs font-semibold tracking-[0.28em] text-slate-500 uppercase"
                        >
                            {{ surface.audience }}
                        </p>
                        <h1
                            class="text-3xl font-semibold tracking-tight sm:text-4xl"
                        >
                            {{ surface.title }}
                        </h1>
                        <p
                            class="max-w-3xl text-sm leading-6 text-slate-600 sm:text-base"
                        >
                            {{ surface.summary }}
                        </p>
                    </div>
                </div>

                <div
                    class="grid gap-4 px-5 py-5 sm:px-7 lg:grid-cols-[1.45fr_0.95fr]"
                >
                    <section class="space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <h2
                                class="text-sm font-semibold tracking-[0.22em] text-slate-500 uppercase"
                            >
                                Modules
                            </h2>
                            <span class="text-sm text-slate-500">
                                {{ surface.modules.length }} planned groups
                            </span>
                        </div>

                        <div class="grid gap-3">
                            <article
                                v-for="module in surface.modules"
                                :key="module.key"
                                class="rounded-3xl border border-slate-200 bg-slate-50/80 p-4 shadow-sm"
                            >
                                <div
                                    class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div class="space-y-1">
                                        <h3
                                            class="text-lg font-semibold text-slate-900"
                                        >
                                            {{ module.title }}
                                        </h3>
                                        <p
                                            class="text-sm leading-6 text-slate-600"
                                        >
                                            {{ module.description }}
                                        </p>
                                    </div>
                                    <span
                                        class="inline-flex w-fit rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500"
                                    >
                                        {{ module.routePrefix }}
                                    </span>
                                </div>
                            </article>
                        </div>
                    </section>

                    <aside class="space-y-4">
                        <div
                            class="rounded-3xl bg-slate-950 p-5 text-slate-50 shadow-lg"
                        >
                            <p
                                class="text-xs font-semibold tracking-[0.24em] text-sky-300 uppercase"
                            >
                                Delivery note
                            </p>
                            <p class="mt-3 text-sm leading-6 text-slate-200">
                                {{ surface.deliveryNote }}
                            </p>
                        </div>

                        <div
                            class="rounded-3xl border border-dashed border-slate-300 p-5"
                        >
                            <p
                                class="text-xs font-semibold tracking-[0.24em] text-slate-500 uppercase"
                            >
                                Why this exists now
                            </p>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                This page establishes the product surface
                                boundary, route prefix, and module grouping
                                before we add the dedicated mobile navigation,
                                auth recovery, and individual feature screens in
                                later tasks.
                            </p>
                        </div>

                        <Link
                            :href="workspaceHref"
                            class="inline-flex w-full items-center justify-between rounded-3xl border border-slate-200 bg-white px-4 py-4 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                        >
                            {{
                                page.props.auth?.user
                                    ? 'Open authenticated workspace hub'
                                    : 'Open current auth flow'
                            }}
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</template>
