<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, CircleUserRound, Mail, Phone } from '@lucide/vue';
import { computed } from 'vue';

type CustomerProfile = {
    id: number;
    user_id: number;
    name: string | null;
    mobile_number: string | null;
    email: string | null;
};

const props = defineProps<{
    profile: CustomerProfile;
}>();

const initials = computed(() => {
    const segments = props.profile.name
        ? props.profile.name
              .split(/\s+/)
              .map((segment) => segment.trim())
              .filter(Boolean)
              .slice(0, 2)
        : [];

    if (segments.length === 0) {
        return 'CP';
    }

    return segments.map((segment) => segment[0]?.toUpperCase() ?? '').join('');
});

const profileRows = computed(() => [
    {
        key: 'name',
        label: 'Full name',
        value: props.profile.name ?? 'Not added yet',
        icon: CircleUserRound,
    },
    {
        key: 'mobile_number',
        label: 'Mobile number',
        value: props.profile.mobile_number ?? 'Not added yet',
        icon: Phone,
    },
    {
        key: 'email',
        label: 'Email address',
        value: props.profile.email ?? 'Not added yet',
        icon: Mail,
    },
]);
</script>

<template>
    <Head title="Customer Profile" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-5">
        <section
            class="overflow-hidden rounded-[2rem] border border-white/70 bg-white/90 shadow-[0_30px_80px_-42px_rgba(15,23,42,0.4)] backdrop-blur"
        >
            <div
                class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,_rgba(14,165,233,0.16),_transparent_42%),linear-gradient(135deg,_rgba(255,255,255,0.96),_rgba(241,245,249,0.94))] px-5 py-5 sm:px-7 sm:py-6"
            >
                <div class="flex items-start justify-between gap-3">
                    <Link
                        href="/customer"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/90 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        <ChevronLeft class="h-4 w-4" />
                        Customer home
                    </Link>

                    <span
                        class="inline-flex items-center gap-2 rounded-full bg-sky-100 px-3 py-2 text-xs font-semibold tracking-[0.24em] text-sky-900 uppercase"
                    >
                        <CalendarDays class="h-4 w-4" />
                        Account snapshot
                    </span>
                </div>

                <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="space-y-3">
                        <p class="text-xs font-semibold tracking-[0.28em] text-slate-500 uppercase">
                            Customer account
                        </p>
                        <div>
                            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">
                                Your profile
                            </h1>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                                This page uses the same profile source as the authenticated customer API so the browser shell and API clients stay aligned.
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex h-16 w-16 items-center justify-center rounded-[1.6rem] bg-slate-950 text-lg font-semibold tracking-[0.18em] text-white shadow-lg shadow-slate-900/15"
                    >
                        {{ initials }}
                    </div>
                </div>
            </div>

            <div class="grid gap-4 px-5 py-5 sm:px-7 lg:grid-cols-[1.1fr_0.9fr]">
                <section class="space-y-4">
                    <article
                        v-for="row in profileRows"
                        :key="row.key"
                        class="rounded-[1.75rem] border border-slate-200 bg-slate-50/85 p-4 shadow-sm"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-slate-700 shadow-sm"
                            >
                                <component :is="row.icon" class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="text-xs font-semibold tracking-[0.24em] text-slate-500 uppercase">
                                    {{ row.label }}
                                </p>
                                <p class="mt-2 break-words text-base font-semibold text-slate-950 sm:text-lg">
                                    {{ row.value }}
                                </p>
                            </div>
                        </div>
                    </article>
                </section>

                <aside class="space-y-4">
                    <article class="rounded-[1.75rem] bg-slate-950 p-5 text-slate-50 shadow-lg">
                        <p class="text-xs font-semibold tracking-[0.24em] text-sky-300 uppercase">
                            Linked account
                        </p>
                        <p class="mt-3 text-sm leading-6 text-slate-200">
                            Customer profile creation still happens on first authenticated access, and this page preserves that behavior instead of introducing a separate browser-only record flow.
                        </p>

                        <div class="mt-4 rounded-[1.5rem] bg-white/10 px-4 py-3 text-sm text-slate-100">
                            <p class="font-medium">Profile record ID</p>
                            <p class="mt-1 text-sky-100">#{{ profile.id }}</p>
                        </div>
                    </article>

                    <article class="rounded-[1.75rem] border border-dashed border-slate-300 bg-white/70 p-5">
                        <p class="text-xs font-semibold tracking-[0.24em] text-slate-500 uppercase">
                            Next step
                        </p>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Editing is not part of the current completed flow yet, so this screen stays read-only while the existing profile retrieval contract remains stable.
                        </p>
                    </article>
                </aside>
            </div>
        </section>
    </div>
</template>
