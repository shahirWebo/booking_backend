<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, MapPin, Plus } from '@lucide/vue';
import EmptyState from '@/components/feedback/EmptyState.vue';
import { Button } from '@/components/ui/button';

type LocationSummary = {
    id: number;
    name: string;
    city: string;
    state: string;
    timezone: string;
    status: string;
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
    locations: Array<LocationSummary & { routes: { edit?: string } }>;
    routes: {
        create: string;
        index: string;
    };
}>();
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
        </section>

        <section v-if="locations.length" class="grid gap-4 xl:grid-cols-2">
            <article
                v-for="location in locations"
                :key="location.id"
                class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-semibold tracking-tight">
                                {{ location.name }}
                            </h2>
                            <span
                                class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase"
                                :class="
                                    location.status === 'active'
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : 'bg-slate-200 text-slate-700'
                                "
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

                <div class="mt-4 grid gap-2 text-sm text-muted-foreground">
                    <p>Timezone: {{ location.timezone }}</p>
                    <p>
                        Operating windows:
                        {{ location.operating_hours.length }}
                    </p>
                    <p>Amenities: {{ location.amenity_ids.length }}</p>
                    <p>Images: {{ location.images.length }}</p>
                </div>

                <div class="mt-5">
                    <Button as-child class="w-full">
                        <Link :href="`/vendor/locations/${location.id}/edit`">
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
            description="Create the first vendor location to capture operating hours, amenities, and gallery images."
        >
            <Button as-child>
                <Link :href="routes.create">Add location</Link>
            </Button>
        </EmptyState>
    </div>
</template>
