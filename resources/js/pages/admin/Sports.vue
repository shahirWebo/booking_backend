<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Globe, ShieldCheck } from '@lucide/vue';
</script>

<template>
    <Head title="Admin Sports" />

    <div
        class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.14),_transparent_35%),linear-gradient(180deg,_#f8fafc_0%,_#e2e8f0_100%)] px-4 py-6 text-slate-950 sm:px-6 lg:px-8"
    >
        <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
            <div
                class="overflow-hidden rounded-[2rem] border border-white/70 bg-white/85 shadow-[0_30px_80px_-40px_rgba(15,23,42,0.35)] backdrop-blur"
            >
                <div class="border-b border-slate-200/80 px-5 py-5 sm:px-7">
                    <div class="flex items-center justify-between gap-3">
                        <Link
                            href="/admin"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            Admin home
                        </Link>

                        <span
                            class="inline-flex items-center gap-2 rounded-full bg-sky-100 px-3 py-2 text-xs font-semibold tracking-[0.24em] text-sky-800 uppercase"
                        >
                            <ShieldCheck class="h-4 w-4" />
                            Platform sports
                        </span>
                    </div>

                    <div class="mt-5 space-y-3">
                        <p
                            class="text-xs font-semibold tracking-[0.28em] text-slate-500 uppercase"
                        >
                            Admin catalog
                        </p>
                        <h1 class="text-3xl font-semibold tracking-tight">
                            Sports management
                        </h1>
                        <p
                            class="max-w-3xl text-sm leading-6 text-slate-600 sm:text-base"
                        >
                            This admin entry point anchors the sports catalog in
                            the web shell while the authenticated CRUD APIs
                            handle create, update, and delete operations for the
                            platform sports master data.
                        </p>
                    </div>
                </div>

                <div
                    class="grid gap-4 px-5 py-5 sm:px-7 lg:grid-cols-[1.2fr_0.8fr]"
                >
                    <section class="space-y-4">
                        <article
                            class="rounded-3xl border border-slate-200 bg-slate-50/80 p-4 shadow-sm"
                        >
                            <p
                                class="text-xs font-semibold tracking-[0.24em] text-slate-500 uppercase"
                            >
                                Available now
                            </p>
                            <ul
                                class="mt-3 space-y-2 text-sm leading-6 text-slate-700"
                            >
                                <li>
                                    Admin API CRUD routes at
                                    `/api/v1/admin/sports`.
                                </li>
                                <li>
                                    Active/inactive status plus icon and image
                                    metadata.
                                </li>
                                <li>
                                    Canonical seeded sports for local and test
                                    environments.
                                </li>
                            </ul>
                        </article>

                        <article
                            class="rounded-3xl border border-dashed border-slate-300 p-4"
                        >
                            <p
                                class="text-xs font-semibold tracking-[0.24em] text-slate-500 uppercase"
                            >
                                Next UI milestone
                            </p>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                A dedicated admin sports management screen can
                                now build on this route without changing the
                                admin navigation contract again.
                            </p>
                        </article>
                    </section>

                    <aside class="space-y-4">
                        <div
                            class="rounded-3xl bg-slate-950 p-5 text-slate-50 shadow-lg"
                        >
                            <p
                                class="text-xs font-semibold tracking-[0.24em] text-sky-300 uppercase"
                            >
                                Public discovery
                            </p>
                            <p class="mt-3 text-sm leading-6 text-slate-200">
                                The active public sports catalog is exposed
                                separately through the unauthenticated discovery
                                endpoint.
                            </p>

                            <div
                                class="mt-4 rounded-3xl bg-white/10 px-4 py-3 text-sm text-slate-100"
                            >
                                <div
                                    class="flex items-center gap-2 font-medium"
                                >
                                    <Globe class="h-4 w-4" />
                                    <span>`GET /api/v1/sports`</span>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</template>
