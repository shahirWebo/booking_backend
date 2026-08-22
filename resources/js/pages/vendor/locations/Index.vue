<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Clock3,
    ImagePlus,
    MapPin,
    Plus,
    Sparkles,
} from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/feedback/EmptyState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type LocationSummary = {
    id: number;
    name: string;
    city: string;
    state: string;
    timezone: string;
    status: string;
    latitude?: number | null;
    longitude?: number | null;
    amenity_ids: number[];
    operating_hours: Array<{
        weekday: number;
        sequence: number;
        opens_at_time: string;
        closes_at_time: string;
        ends_next_day: boolean;
    }>;
    images: Array<{
        id: number;
    }>;
};

const props = defineProps<{
    vendor: {
        id: number;
        display_name: string | null;
        legal_name: string | null;
    };
    locations: Array<LocationSummary & { routes?: { edit?: string } }>;
    routes: {
        create: string;
        index: string;
    };
}>();

const weekdayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const summary = computed(() => ({
    total: props.locations.length,
    active: props.locations.filter((location) => location.status === 'active')
        .length,
    withCoordinates: props.locations.filter(
        (location) => location.latitude !== null && location.longitude !== null,
    ).length,
    images: props.locations.reduce(
        (count, location) => count + location.images.length,
        0,
    ),
}));

function firstWindowSummary(location: LocationSummary): string {
    const window = location.operating_hours[0];

    if (!window) {
        return 'Add weekday windows';
    }

    return `${weekdayLabels[window.weekday - 1]} ${window.opens_at_time}-${window.closes_at_time}${window.ends_next_day ? ' +' : ''}`;
}

function statusTone(status: string): string {
    return status === 'active'
        ? 'bg-emerald-100 text-emerald-800'
        : 'bg-slate-200 text-slate-700';
}
</script>

<template>
    <Head title="Locations" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <section
            class="overflow-hidden rounded-3xl border border-sidebar-border/70 bg-sidebar-accent/40 p-5 dark:border-sidebar-border"
        >
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
            >
                <div class="space-y-3">
                    <p
                        class="text-xs font-semibold tracking-[0.24em] text-sidebar-foreground/60 uppercase"
                    >
                        Vendor operations
                    </p>
                    <div>
                        <h1
                            class="text-2xl font-semibold tracking-tight text-sidebar-foreground"
                        >
                            Locations
                        </h1>
                        <p
                            class="mt-2 max-w-3xl text-sm leading-6 text-sidebar-foreground/70"
                        >
                            Review and manage venue locations for
                            {{
                                vendor.display_name ??
                                vendor.legal_name ??
                                'your vendor account'
                            }}.
                        </p>
                    </div>
                </div>

                <Button as-child>
                    <Link :href="routes.create">
                        <Plus class="h-4 w-4" />
                        Add location
                    </Link>
                </Button>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl bg-background px-4 py-3">
                    <p
                        class="text-[11px] font-semibold text-muted-foreground uppercase"
                    >
                        Total
                    </p>
                    <p class="mt-1 text-xl font-semibold">
                        {{ summary.total }}
                    </p>
                </div>
                <div class="rounded-2xl bg-background px-4 py-3">
                    <p
                        class="text-[11px] font-semibold text-muted-foreground uppercase"
                    >
                        Active
                    </p>
                    <p class="mt-1 text-xl font-semibold">
                        {{ summary.active }}
                    </p>
                </div>
                <div class="rounded-2xl bg-background px-4 py-3">
                    <p
                        class="text-[11px] font-semibold text-muted-foreground uppercase"
                    >
                        Pinned
                    </p>
                    <p class="mt-1 text-xl font-semibold">
                        {{ summary.withCoordinates }}
                    </p>
                </div>
                <div class="rounded-2xl bg-background px-4 py-3">
                    <p
                        class="text-[11px] font-semibold text-muted-foreground uppercase"
                    >
                        Images
                    </p>
                    <p class="mt-1 text-xl font-semibold">
                        {{ summary.images }}
                    </p>
                </div>
            </div>
        </section>

        <section v-if="locations.length" class="grid gap-4 xl:grid-cols-2">
            <article
                v-for="location in locations"
                :key="location.id"
                class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-semibold tracking-tight">
                                {{ location.name }}
                            </h2>
                            <span
                                class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase"
                                :class="statusTone(location.status)"
                            >
                                {{ location.status }}
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ location.city }}, {{ location.state }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-muted/70"
                    >
                        <MapPin class="h-5 w-5 text-muted-foreground" />
                    </div>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-muted/30 px-4 py-3">
                        <p
                            class="text-[11px] font-semibold text-muted-foreground uppercase"
                        >
                            Hours
                        </p>
                        <p class="mt-1 text-sm text-foreground">
                            {{ firstWindowSummary(location) }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ location.operating_hours.length }} windows
                        </p>
                    </div>

                    <div class="rounded-2xl bg-muted/30 px-4 py-3">
                        <p
                            class="text-[11px] font-semibold text-muted-foreground uppercase"
                        >
                            Gallery
                        </p>
                        <p class="mt-1 text-sm text-foreground">
                            {{ location.images.length }} image{{
                                location.images.length === 1 ? '' : 's'
                            }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ location.timezone }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <Badge variant="secondary" class="rounded-full">
                        <Clock3 class="mr-1 h-3.5 w-3.5" />
                        {{ location.operating_hours.length }} hours
                    </Badge>
                    <Badge variant="secondary" class="rounded-full">
                        <Sparkles class="mr-1 h-3.5 w-3.5" />
                        {{ location.amenity_ids.length }} amenities
                    </Badge>
                    <Badge variant="secondary" class="rounded-full">
                        <ImagePlus class="mr-1 h-3.5 w-3.5" />
                        {{ location.images.length }} gallery
                    </Badge>
                    <Badge variant="secondary" class="rounded-full">
                        <MapPin class="mr-1 h-3.5 w-3.5" />
                        {{
                            location.latitude !== null &&
                            location.longitude !== null
                                ? 'Coordinates set'
                                : 'Coordinates pending'
                        }}
                    </Badge>
                </div>

                <div class="mt-5">
                    <Button as-child class="w-full">
                        <Link
                            :href="
                                location.routes?.edit ??
                                `/vendor/locations/${location.id}/edit`
                            "
                        >
                            Edit location
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </Button>
                </div>
            </article>
        </section>

        <EmptyState
            v-else
            title="No locations yet"
            description="Create the first vendor location to capture operating hours, amenities, a map pin, and gallery images."
        >
            <Button as-child>
                <Link :href="routes.create">Add location</Link>
            </Button>
        </EmptyState>
    </div>
</template>
