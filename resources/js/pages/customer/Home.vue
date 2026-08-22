<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    CalendarDays,
    ChevronRight,
    CircleUserRound,
    Dumbbell,
    Heart,
    House,
    MapPin,
    Search,
    Trophy,
    UsersRound,
    Volleyball,
} from '@lucide/vue';
import { computed } from 'vue';
import customer from '@/routes/customer';

type SharedUser = { name: string | null };

type NearbyTurf = {
    id: number;
    name: string;
    distance_meters: number | null;
    location: { city: string; locality: string | null };
    pricing_summary: { currency: string | null; starting_price: string | null };
    detail_url: string;
};

const props = defineProps<{ nearbyTurfs: NearbyTurf[] }>();
const page = usePage<{ auth: { user: SharedUser | null } }>();
const firstName = computed(
    () =>
        (page.props.auth.user?.name ?? 'Player').trim().split(/\s+/)[0] ||
        'Player',
);

const categories = [
    { label: 'Cricket', icon: Trophy, tone: 'bg-sky-100 text-sky-700' },
    { label: 'Football', icon: Dumbbell, tone: 'bg-rose-100 text-rose-600' },
    {
        label: 'Badminton',
        icon: Volleyball,
        tone: 'bg-amber-100 text-amber-700',
    },
    {
        label: 'Basketball',
        icon: CircleUserRound,
        tone: 'bg-orange-100 text-orange-600',
    },
];

const navigation = [
    { label: 'Home', icon: House, href: customer.home(), active: true },
    { label: 'Events', icon: CalendarDays, href: customer.search.index() },
    { label: 'Coaching', icon: Trophy, href: customer.search.index() },
    { label: 'Community', icon: UsersRound, href: customer.search.index() },
    { label: 'Profile', icon: CircleUserRound, href: customer.profile.show() },
];
</script>

<template>
    <Head title="Find your game" />

    <main class="min-h-dvh bg-[#f8faf5] pb-24 text-slate-950">
        <div class="mx-auto w-full max-w-md px-5 pt-6 sm:max-w-2xl sm:px-7">
            <header class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-950">
                        Hi, {{ firstName }}
                    </p>
                    <button
                        type="button"
                        class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-slate-500"
                    >
                        <MapPin class="h-3.5 w-3.5 text-emerald-600" />
                        Choose your area
                        <ChevronRight class="h-3.5 w-3.5" />
                    </button>
                </div>
                <div class="flex items-center gap-2 text-slate-400">
                    <button
                        type="button"
                        aria-label="Saved turfs"
                        class="grid h-10 w-10 place-items-center rounded-full bg-white shadow-[0_8px_22px_-14px_rgba(15,23,42,0.42)] transition hover:text-rose-500"
                    >
                        <Heart class="h-5 w-5" />
                    </button>
                    <button
                        type="button"
                        aria-label="Notifications"
                        class="grid h-10 w-10 place-items-center rounded-full bg-white shadow-[0_8px_22px_-14px_rgba(15,23,42,0.42)] transition hover:text-emerald-600"
                    >
                        <Bell class="h-5 w-5" />
                    </button>
                </div>
            </header>

            <Link
                :href="customer.search.index()"
                class="mt-6 flex h-12 items-center gap-3 rounded-2xl bg-lime-100 px-4 text-sm text-slate-500 shadow-[0_14px_30px_-22px_rgba(77,124,15,0.5)] transition hover:bg-lime-200"
            >
                <Search class="h-5 w-5 text-emerald-700" />
                <span>What sport are you looking for?</span>
            </Link>

            <section
                class="relative isolate mt-5 overflow-hidden rounded-[1.6rem] bg-gradient-to-br from-emerald-700 via-lime-600 to-lime-400 p-5 text-white shadow-[0_24px_40px_-24px_rgba(21,128,61,0.78)]"
            >
                <div
                    class="absolute -top-10 -right-9 h-44 w-44 rounded-full border-[22px] border-white/15"
                />
                <div
                    class="absolute top-5 right-8 h-28 w-28 rounded-full border border-white/45 shadow-[inset_0_0_0_12px_rgba(255,255,255,0.78),0_12px_20px_rgba(15,23,42,0.26)] [background:repeating-conic-gradient(from_15deg,_rgba(15,23,42,0.75)_0_9deg,_transparent_9deg_27deg)]"
                />
                <div
                    class="absolute -right-6 bottom-0 h-32 w-48 rotate-[-20deg] border-t border-l border-white/35"
                />
                <p class="relative text-xs font-medium text-lime-100">
                    Your next match starts here
                </p>
                <h1
                    class="relative mt-2 max-w-[11rem] text-2xl leading-7 font-semibold tracking-tight"
                >
                    Find a court for tonight
                </h1>
                <p class="relative mt-1 text-sm text-emerald-50">
                    Search local turfs in seconds.
                </p>
                <Link
                    :href="customer.search.index()"
                    class="relative mt-4 inline-flex items-center gap-1 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50"
                    >Explore turfs <ChevronRight class="h-3.5 w-3.5"
                /></Link>
            </section>

            <section class="mt-7">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold tracking-tight">
                        Categories
                    </h2>
                    <Link
                        :href="customer.search.index()"
                        class="text-xs font-semibold text-emerald-700"
                        >See all</Link
                    >
                </div>
                <div
                    class="mt-3 -mr-5 flex [scrollbar-width:none] gap-3 overflow-x-auto pr-5 pb-1 sm:-mr-7 sm:pr-7"
                >
                    <Link
                        v-for="category in categories"
                        :key="category.label"
                        :href="
                            customer.search.index({
                                query: { turf_name: category.label },
                            })
                        "
                        class="flex w-[4.8rem] shrink-0 flex-col items-center gap-2 rounded-2xl bg-white px-2 py-3 shadow-[0_10px_22px_-18px_rgba(15,23,42,0.55)] transition hover:-translate-y-0.5"
                        ><span
                            :class="[
                                'grid h-9 w-9 place-items-center rounded-xl',
                                category.tone,
                            ]"
                            ><component
                                :is="category.icon"
                                class="h-4 w-4" /></span
                        ><span
                            class="text-center text-[10px] font-medium text-slate-600"
                            >{{ category.label }}</span
                        ></Link
                    >
                </div>
            </section>

            <section class="mt-7">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold tracking-tight">
                            Nearby arenas
                        </h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Find a place for your squad.
                        </p>
                    </div>
                    <Link
                        :href="customer.search.index()"
                        class="text-xs font-semibold text-emerald-700"
                        >View all</Link
                    >
                </div>
                <div
                    v-if="props.nearbyTurfs.length"
                    class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3"
                >
                    <Link
                        v-for="(turf, index) in props.nearbyTurfs"
                        :key="turf.id"
                        :href="turf.detail_url"
                        class="overflow-hidden rounded-2xl bg-white shadow-[0_14px_28px_-20px_rgba(15,23,42,0.64)] transition hover:-translate-y-0.5"
                    >
                        <div
                            class="relative h-24 overflow-hidden"
                            :class="
                                index % 2 === 0
                                    ? 'bg-[radial-gradient(circle_at_45%_52%,#ef4444_0_16%,#991b1b_17%_22%,transparent_23%),linear-gradient(145deg,#55322b,#c08457_48%,#2e4d2b)]'
                                    : 'bg-[linear-gradient(130deg,transparent_0_35%,rgba(255,255,255,0.8)_36%_39%,transparent_40%_100%),linear-gradient(160deg,#154a2a,#6cae50_46%,#174128)]'
                            "
                        />
                        <div class="p-3">
                            <p class="truncate text-xs font-semibold">
                                {{ turf.name }}
                            </p>
                            <p class="mt-1 text-[10px] text-slate-500">
                                {{
                                    turf.distance_meters !== null
                                        ? `${Math.round(turf.distance_meters / 1000)} km away`
                                        : [
                                              turf.location.locality,
                                              turf.location.city,
                                          ]
                                              .filter(Boolean)
                                              .join(', ')
                                }}
                                ·
                                {{
                                    turf.pricing_summary.starting_price
                                        ? `from ${turf.pricing_summary.currency ?? ''} ${turf.pricing_summary.starting_price}`
                                        : 'Pricing pending'
                                }}
                            </p>
                        </div></Link
                    >
                </div>
                <div
                    v-else
                    class="mt-3 rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/60 p-4 text-sm text-slate-600"
                >
                    No nearby arenas are available yet. Explore all turfs to
                    widen your search.
                </div>
            </section>
        </div>

        <nav
            aria-label="Customer navigation"
            class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200/80 bg-white/95 px-4 pt-2 pb-[max(0.65rem,env(safe-area-inset-bottom))] backdrop-blur"
        >
            <div class="mx-auto grid max-w-md grid-cols-5 gap-1">
                <Link
                    v-for="item in navigation"
                    :key="item.label"
                    :href="item.href"
                    class="flex min-h-12 flex-col items-center justify-center gap-1 rounded-xl text-[10px] font-medium"
                    :class="
                        item.active
                            ? 'text-emerald-700'
                            : 'text-slate-400 hover:text-slate-700'
                    "
                    ><component
                        :is="item.icon"
                        class="h-[18px] w-[18px]"
                        :stroke-width="item.active ? 2.5 : 1.8"
                    />{{ item.label }}</Link
                >
            </div>
        </nav>
    </main>
</template>
