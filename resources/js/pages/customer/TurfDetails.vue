<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, MapPin, Sparkles } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    turf: {
        id: number;
        name: string;
        description: string | null;
        surface_type: string | null;
        is_indoor: boolean | null;
        capacity_count: number | null;
        location: {
            name: string;
            address_line_1: string;
            address_line_2: string | null;
            landmark: string | null;
            locality: string | null;
            city: string;
            state: string;
            postal_code: string;
            country_code: string;
            latitude: number | null;
            longitude: number | null;
            timezone: string;
        };
        sports: Array<{ id: number; name: string }>;
        amenities: Array<{ id: number; name: string }>;
        pricing_summary: {
            currency: string | null;
            starting_price: string | null;
            highest_price: string | null;
        };
        availability_summary: {
            date: string;
            has_availability: boolean;
            available_slots_count: number;
            sample_slots: Array<{
                starts_at_time: string;
                ends_at_time: string;
            }>;
        };
        rules: Array<{ id: number; title: string; description: string }>;
        images: Array<{ id: number; caption: string | null; alt_text: string | null; original_name: string | null }>;
    };
    routes: {
        search: string;
        show: string;
    };
}>();

const locationLine = computed(() =>
    [
        props.turf.location.address_line_1,
        props.turf.location.address_line_2,
        props.turf.location.landmark,
        props.turf.location.locality,
        props.turf.location.city,
        props.turf.location.state,
        props.turf.location.postal_code,
    ]
        .filter(Boolean)
        .join(', '),
);
</script>

<template>
    <Head :title="turf.name" />

    <main class="mx-auto flex w-full max-w-5xl flex-col gap-5 p-4 pb-10">
        <section
            class="overflow-hidden rounded-[2rem] border border-slate-200 bg-[radial-gradient(circle_at_top_right,_rgba(15,118,110,0.18),_transparent_36%),linear-gradient(135deg,_rgba(255,255,255,0.98),_rgba(241,245,249,0.96))] p-5 shadow-[0_24px_70px_-46px_rgba(15,23,42,0.35)]"
        >
            <Link
                :href="routes.search"
                class="inline-flex items-center gap-2 rounded-full border border-white/80 bg-white/90 px-3 py-2 text-sm font-medium text-slate-700"
            >
                <ArrowLeft class="h-4 w-4" />
                Back to search
            </Link>

            <div class="mt-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl space-y-3">
                    <p class="text-xs font-semibold tracking-[0.24em] text-teal-800 uppercase">
                        {{ turf.location.name }} · {{ turf.location.timezone }}
                    </p>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-950">
                        {{ turf.name }}
                    </h1>
                    <p class="text-sm leading-6 text-slate-600 sm:text-base">
                        {{
                            turf.description ??
                            'This turf is live for discovery. Venue rules, amenities, and slot previews are listed below.'
                        }}
                    </p>
                </div>

                <div class="rounded-[1.5rem] bg-slate-950 p-4 text-white">
                    <p class="text-xs font-semibold tracking-[0.24em] text-emerald-300 uppercase">
                        Pricing summary
                    </p>
                    <p class="mt-2 text-2xl font-semibold">
                        {{
                            turf.pricing_summary.starting_price
                                ? `${turf.pricing_summary.currency} ${turf.pricing_summary.starting_price}`
                                : 'Pricing pending'
                        }}
                    </p>
                    <p
                        v-if="turf.pricing_summary.highest_price"
                        class="mt-1 text-sm text-slate-300"
                    >
                        Up to {{ turf.pricing_summary.currency }}
                        {{ turf.pricing_summary.highest_price }}
                    </p>
                </div>
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
            <div class="grid gap-5">
                <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <MapPin class="h-5 w-5 text-emerald-700" />
                        <h2 class="text-xl font-semibold">Location</h2>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        {{ locationLine }}
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        Coordinates:
                        {{
                            turf.location.latitude !== null &&
                            turf.location.longitude !== null
                                ? `${turf.location.latitude}, ${turf.location.longitude}`
                                : 'Pending'
                        }}
                    </p>
                </article>

                <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <Sparkles class="h-5 w-5 text-slate-900" />
                        <h2 class="text-xl font-semibold">Sports and amenities</h2>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span
                            v-for="sport in turf.sports"
                            :key="sport.id"
                            class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-800"
                        >
                            {{ sport.name }}
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span
                            v-for="amenity in turf.amenities"
                            :key="amenity.id"
                            class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700"
                        >
                            {{ amenity.name }}
                        </span>
                    </div>
                </article>

                <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-semibold">Venue rules</h2>
                    <div v-if="turf.rules.length" class="mt-4 grid gap-3">
                        <div
                            v-for="rule in turf.rules"
                            :key="rule.id"
                            class="rounded-2xl bg-slate-50 p-4"
                        >
                            <h3 class="font-semibold text-slate-900">
                                {{ rule.title }}
                            </h3>
                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                {{ rule.description }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="mt-3 text-sm text-slate-500">
                        House rules will appear here as vendors complete their turf profile.
                    </p>
                </article>
            </div>

            <div class="grid gap-5">
                <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <CalendarDays class="h-5 w-5 text-amber-700" />
                        <h2 class="text-xl font-semibold">Availability</h2>
                    </div>

                    <p class="mt-3 text-sm text-slate-600">
                        {{ turf.availability_summary.date }} ·
                        {{
                            turf.availability_summary.has_availability
                                ? `${turf.availability_summary.available_slots_count} slots available`
                                : 'No slots available'
                        }}
                    </p>

                    <div v-if="turf.availability_summary.sample_slots.length" class="mt-4 grid gap-2">
                        <div
                            v-for="slot in turf.availability_summary.sample_slots"
                            :key="`${slot.starts_at_time}-${slot.ends_at_time}`"
                            class="rounded-2xl bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900"
                        >
                            {{ slot.starts_at_time }} - {{ slot.ends_at_time }}
                        </div>
                    </div>
                </article>

                <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-semibold">Quick facts</h2>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Surface</dt>
                            <dd class="font-medium text-slate-900">
                                {{ turf.surface_type ?? 'Pending' }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Setting</dt>
                            <dd class="font-medium text-slate-900">
                                {{
                                    turf.is_indoor === null
                                        ? 'Pending'
                                        : turf.is_indoor
                                          ? 'Indoor'
                                          : 'Outdoor'
                                }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Capacity</dt>
                            <dd class="font-medium text-slate-900">
                                {{ turf.capacity_count ?? 'Pending' }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Image records</dt>
                            <dd class="font-medium text-slate-900">
                                {{ turf.images.length }}
                            </dd>
                        </div>
                    </dl>
                </article>

                <Button as-child>
                    <Link :href="routes.search">Find another turf</Link>
                </Button>
            </div>
        </section>
    </main>
</template>
