<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowRight,
    Compass,
    MapPin,
    Search as SearchIcon,
    SlidersHorizontal,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/feedback/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';

type Option = {
    id: number;
    name: string;
};

type LocationArea = {
    city: string;
    locality: string | null;
};

type ResultItem = {
    id: number;
    name: string;
    description: string | null;
    is_indoor: boolean | null;
    distance_meters: number | null;
    sports: Array<{ id: number; name: string }>;
    amenities: Array<{ id: number; name: string }>;
    location: {
        name: string;
        locality: string | null;
        city: string;
        state: string;
    };
    pricing_summary: {
        currency: string | null;
        starting_price: string | null;
    };
    availability_summary: {
        has_availability: boolean;
        available_slots_count: number;
        first_slot: null | {
            starts_at_time: string;
            ends_at_time: string;
        };
    } | null;
    detail_url: string;
};

const props = defineProps<{
    filters: {
        latitude: string | null;
        longitude: string | null;
        city: string | null;
        locality: string | null;
        turf_name: string | null;
        sport_ids: number[];
        amenity_ids: number[];
        min_price: string | null;
        max_price: string | null;
        distance_meters: string | null;
        date: string | null;
        is_indoor: boolean | null;
        sort: string;
        per_page: number;
    };
    options: {
        sports: Option[];
        amenities: Option[];
        location_areas: LocationArea[];
        sorts: Array<{ value: string; label: string }>;
    };
    results: {
        data: ResultItem[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number | null;
            to: number | null;
        };
        links: {
            prev: string | null;
            next: string | null;
        };
    };
    routes: {
        index: string;
    };
    sort_support: {
        rating: boolean;
        popularity: boolean;
    };
}>();

const form = useForm({
    latitude: props.filters.latitude ?? '',
    longitude: props.filters.longitude ?? '',
    city: props.filters.city ?? '',
    locality: props.filters.locality ?? '',
    turf_name: props.filters.turf_name ?? '',
    sport_ids: [...props.filters.sport_ids],
    amenity_ids: [...props.filters.amenity_ids],
    min_price: props.filters.min_price ?? '',
    max_price: props.filters.max_price ?? '',
    distance_meters: props.filters.distance_meters ?? '',
    date: props.filters.date ?? '',
    is_indoor:
        props.filters.is_indoor === null
            ? ''
            : props.filters.is_indoor
              ? '1'
              : '0',
    sort: props.filters.sort,
    per_page: props.filters.per_page.toString(),
});

const sortWarnings = computed(() => ({
    rating: !props.sort_support.rating,
    popularity: !props.sort_support.popularity,
}));

const geolocationSupported =
    typeof navigator !== 'undefined' &&
    typeof navigator.geolocation?.getCurrentPosition === 'function';

const isRequestingLocation = ref(false);
const isFilterSheetOpen = ref(false);
const geolocationMessage = ref(
    geolocationSupported
        ? 'Use your device location to automatically search nearby turfs.'
        : 'Browser location is unavailable here, so you can still enter coordinates manually.',
);
const geolocationMessageTone = ref<'default' | 'error'>('default');

function toggleSelection(key: 'sport_ids' | 'amenity_ids', id: number): void {
    if (form[key].includes(id)) {
        form[key] = form[key].filter((value) => value !== id);

        return;
    }

    form[key] = [...form[key], id];
}

function submit(): void {
    form.get(props.routes.index, {
        preserveScroll: true,
        preserveState: true,
    });
}

function applyFilters(): void {
    isFilterSheetOpen.value = false;
    submit();
}

function selectManualArea(event: Event): void {
    const selectedArea = props.options.location_areas.find(
        (_, index) =>
            index.toString() === (event.target as HTMLSelectElement).value,
    );

    if (!selectedArea) {
        return;
    }

    form.city = selectedArea.city;
    form.locality = selectedArea.locality ?? '';
    form.latitude = '';
    form.longitude = '';
    form.distance_meters = '';

    if (form.sort === 'distance') {
        form.sort = 'recommended';
    }

    geolocationMessage.value = 'Area selected. Search when you are ready.';
    geolocationMessageTone.value = 'default';
}

function requestCurrentLocation(): void {
    if (!geolocationSupported || isRequestingLocation.value) {
        geolocationMessage.value =
            'Browser location is unavailable here, so you can still enter coordinates manually.';
        geolocationMessageTone.value = 'error';

        return;
    }

    isRequestingLocation.value = true;
    geolocationMessage.value =
        'Waiting for your browser to share the current location...';
    geolocationMessageTone.value = 'default';

    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitude = position.coords.latitude.toFixed(6);
            form.longitude = position.coords.longitude.toFixed(6);

            if (form.sort === 'recommended') {
                form.sort = 'distance';
            }

            geolocationMessage.value =
                'Location added. Refreshing nearby turf results now.';
            geolocationMessageTone.value = 'default';
            isRequestingLocation.value = false;
            submit();
        },
        (error) => {
            geolocationMessageTone.value = 'error';

            switch (error.code) {
                case error.PERMISSION_DENIED:
                    geolocationMessage.value =
                        'Location permission was denied. Enter coordinates manually or allow access and try again.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    geolocationMessage.value =
                        'Your device could not determine a location just now. Please try again.';
                    break;
                case error.TIMEOUT:
                    geolocationMessage.value =
                        'Location lookup timed out. Please try again.';
                    break;
                default:
                    geolocationMessage.value =
                        'We could not read your location. Please try again or enter coordinates manually.';
                    break;
            }

            isRequestingLocation.value = false;
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000,
        },
    );
}
</script>

<template>
    <Head title="Find Turfs" />

    <main class="mx-auto flex w-full max-w-6xl flex-col gap-5 p-4 pb-10">
        <section
            class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-[radial-gradient(circle_at_top_right,_rgba(16,185,129,0.22),_transparent_34%),linear-gradient(135deg,_rgba(255,255,255,0.98),_rgba(236,253,245,0.94))] p-5 shadow-[0_24px_70px_-44px_rgba(5,150,105,0.45)]"
        >
            <p
                class="text-xs font-semibold tracking-[0.26em] text-emerald-800 uppercase"
            >
                Customer discovery
            </p>
            <div
                class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
            >
                <div class="max-w-3xl space-y-2">
                    <h1
                        class="text-3xl font-semibold tracking-tight text-slate-950"
                    >
                        Find a turf that fits today’s game plan
                    </h1>
                    <p class="text-sm leading-6 text-slate-600 sm:text-base">
                        Search by place, sport, amenities, price, distance, and
                        date. Distance, price, and slot summaries are always
                        server calculated.
                    </p>
                </div>

                <Button type="button" @click="submit">
                    <SearchIcon class="h-4 w-4" />
                    Refresh results
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    class="lg:hidden"
                    @click="isFilterSheetOpen = true"
                >
                    <SlidersHorizontal class="h-4 w-4" />
                    Filters
                </Button>
            </div>
        </section>

        <Sheet v-model:open="isFilterSheetOpen">
            <SheetContent
                side="bottom"
                class="h-[88dvh] rounded-t-[2rem] border-x-0 px-5 pt-7"
            >
                <SheetHeader class="pr-8 text-left">
                    <SheetTitle>Refine your search</SheetTitle>
                    <SheetDescription>
                        Narrow results by place, sport, price, and availability.
                    </SheetDescription>
                </SheetHeader>

                <form
                    class="mt-2 flex min-h-0 flex-1 flex-col"
                    @submit.prevent="applyFilters"
                >
                    <div class="grid flex-1 gap-4 overflow-y-auto pr-1 pb-5">
                        <div class="grid gap-2">
                            <Label for="mobile-turf-name">Turf name</Label>
                            <Input
                                id="mobile-turf-name"
                                v-model="form.turf_name"
                                name="turf_name"
                                placeholder="Search by turf name"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="mobile-manual-area"
                                >Choose an area</Label
                            >
                            <select
                                id="mobile-manual-area"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                @change="selectManualArea"
                            >
                                <option value="">Choose a listed area</option>
                                <option
                                    v-for="(
                                        area, index
                                    ) in options.location_areas"
                                    :key="`mobile-${area.city}-${area.locality ?? 'city'}`"
                                    :value="index"
                                >
                                    {{
                                        [area.locality, area.city]
                                            .filter(Boolean)
                                            .join(', ')
                                    }}
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="mobile-city">City</Label>
                                <Input
                                    id="mobile-city"
                                    v-model="form.city"
                                    name="city"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="mobile-locality">Locality</Label>
                                <Input
                                    id="mobile-locality"
                                    v-model="form.locality"
                                    name="locality"
                                />
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-3"
                        >
                            <p class="text-sm font-medium text-slate-900">
                                Nearby search
                            </p>
                            <p
                                class="mt-1 text-xs leading-5"
                                :class="
                                    geolocationMessageTone === 'error'
                                        ? 'text-rose-700'
                                        : 'text-slate-600'
                                "
                            >
                                {{ geolocationMessage }}
                            </p>
                            <Button
                                type="button"
                                variant="outline"
                                class="mt-3 border-emerald-200 bg-white"
                                :disabled="
                                    !geolocationSupported ||
                                    isRequestingLocation ||
                                    form.processing
                                "
                                @click="requestCurrentLocation"
                            >
                                <Compass class="h-4 w-4" />
                                {{
                                    isRequestingLocation
                                        ? 'Locating...'
                                        : 'Use my location'
                                }}
                            </Button>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="mobile-min-price">Min price</Label>
                                <Input
                                    id="mobile-min-price"
                                    v-model="form.min_price"
                                    name="min_price"
                                    inputmode="decimal"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="mobile-max-price">Max price</Label>
                                <Input
                                    id="mobile-max-price"
                                    v-model="form.max_price"
                                    name="max_price"
                                    inputmode="decimal"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="mobile-date"
                                    >Availability date</Label
                                >
                                <Input
                                    id="mobile-date"
                                    v-model="form.date"
                                    type="date"
                                    name="date"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="mobile-is-indoor">Setting</Label>
                                <select
                                    id="mobile-is-indoor"
                                    v-model="form.is_indoor"
                                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="">Indoor or outdoor</option>
                                    <option value="1">Indoor</option>
                                    <option value="0">Outdoor</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label>Sports</Label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="sport in options.sports"
                                    :key="`mobile-sport-${sport.id}`"
                                    type="button"
                                    class="rounded-full border px-3 py-2 text-sm transition"
                                    :class="
                                        form.sport_ids.includes(sport.id)
                                            ? 'border-emerald-600 bg-emerald-600 text-white'
                                            : 'border-slate-200 bg-white text-slate-700'
                                    "
                                    @click="
                                        toggleSelection('sport_ids', sport.id)
                                    "
                                >
                                    {{ sport.name }}
                                </button>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label>Amenities</Label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="amenity in options.amenities"
                                    :key="`mobile-amenity-${amenity.id}`"
                                    type="button"
                                    class="rounded-full border px-3 py-2 text-sm transition"
                                    :class="
                                        form.amenity_ids.includes(amenity.id)
                                            ? 'border-slate-900 bg-slate-900 text-white'
                                            : 'border-slate-200 bg-white text-slate-700'
                                    "
                                    @click="
                                        toggleSelection(
                                            'amenity_ids',
                                            amenity.id,
                                        )
                                    "
                                >
                                    {{ amenity.name }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <Button type="submit" class="mt-3 w-full">
                        <SearchIcon class="h-4 w-4" />
                        Show results
                    </Button>
                </form>
            </SheetContent>
        </Sheet>

        <section class="grid gap-5 lg:grid-cols-[1.05fr_1.95fr]">
            <form
                class="hidden rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm lg:block"
                @submit.prevent="submit"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-800"
                    >
                        <Compass class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">Search filters</h2>
                        <p class="text-sm text-slate-500">
                            Start broad, then tighten by price or amenities.
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4">
                    <div class="grid gap-2">
                        <Label for="manual-area">Choose an area</Label>
                        <select
                            id="manual-area"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            @change="selectManualArea"
                        >
                            <option value="">Choose a listed area</option>
                            <option
                                v-for="(area, index) in options.location_areas"
                                :key="`${area.city}-${area.locality ?? 'city'}`"
                                :value="index"
                            >
                                {{
                                    [area.locality, area.city]
                                        .filter(Boolean)
                                        .join(', ')
                                }}
                            </option>
                        </select>
                        <p class="text-xs leading-5 text-slate-500">
                            Choose an area or enter a city and locality below.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="city">City</Label>
                        <Input id="city" v-model="form.city" name="city" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="locality">Locality</Label>
                        <Input
                            id="locality"
                            v-model="form.locality"
                            name="locality"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="turf-name">Turf name</Label>
                        <Input
                            id="turf-name"
                            v-model="form.turf_name"
                            name="turf_name"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label for="latitude">Latitude</Label>
                            <Input
                                id="latitude"
                                v-model="form.latitude"
                                name="latitude"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="longitude">Longitude</Label>
                            <Input
                                id="longitude"
                                v-model="form.longitude"
                                name="longitude"
                            />
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-3"
                    >
                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-slate-900">
                                    Nearby search
                                </p>
                                <p
                                    class="text-xs leading-5"
                                    :class="
                                        geolocationMessageTone === 'error'
                                            ? 'text-rose-700'
                                            : 'text-slate-600'
                                    "
                                >
                                    {{ geolocationMessage }}
                                </p>
                            </div>

                            <Button
                                type="button"
                                variant="outline"
                                class="border-emerald-200 bg-white"
                                :disabled="
                                    !geolocationSupported ||
                                    isRequestingLocation ||
                                    form.processing
                                "
                                @click="requestCurrentLocation"
                            >
                                <Compass class="h-4 w-4" />
                                {{
                                    isRequestingLocation
                                        ? 'Locating...'
                                        : 'Use my location'
                                }}
                            </Button>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="distance-meters">Distance meters</Label>
                        <Input
                            id="distance-meters"
                            v-model="form.distance_meters"
                            name="distance_meters"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label for="min-price">Min price</Label>
                            <Input
                                id="min-price"
                                v-model="form.min_price"
                                name="min_price"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="max-price">Max price</Label>
                            <Input
                                id="max-price"
                                v-model="form.max_price"
                                name="max_price"
                            />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="date">Availability date</Label>
                        <Input
                            id="date"
                            v-model="form.date"
                            type="date"
                            name="date"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label for="is-indoor">Setting</Label>
                            <select
                                id="is-indoor"
                                v-model="form.is_indoor"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">Indoor or outdoor</option>
                                <option value="1">Indoor</option>
                                <option value="0">Outdoor</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="sort">Sort</Label>
                            <select
                                id="sort"
                                v-model="form.sort"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option
                                    v-for="sortOption in options.sorts"
                                    :key="sortOption.value"
                                    :value="sortOption.value"
                                >
                                    {{ sortOption.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <p
                        v-if="
                            (form.sort === 'rating' && sortWarnings.rating) ||
                            (form.sort === 'popularity' &&
                                sortWarnings.popularity)
                        "
                        class="text-xs leading-5 text-amber-700"
                    >
                        Rating and popularity sorting are wired into the search
                        contract, but the current dataset does not yet include
                        review or booking-volume scores.
                    </p>

                    <div class="grid gap-2">
                        <Label>Sports</Label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="sport in options.sports"
                                :key="sport.id"
                                type="button"
                                class="rounded-full border px-3 py-2 text-sm transition"
                                :class="
                                    form.sport_ids.includes(sport.id)
                                        ? 'border-emerald-600 bg-emerald-600 text-white'
                                        : 'border-slate-200 bg-white text-slate-700'
                                "
                                @click="toggleSelection('sport_ids', sport.id)"
                            >
                                {{ sport.name }}
                            </button>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label>Amenities</Label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="amenity in options.amenities"
                                :key="amenity.id"
                                type="button"
                                class="rounded-full border px-3 py-2 text-sm transition"
                                :class="
                                    form.amenity_ids.includes(amenity.id)
                                        ? 'border-slate-900 bg-slate-900 text-white'
                                        : 'border-slate-200 bg-white text-slate-700'
                                "
                                @click="
                                    toggleSelection('amenity_ids', amenity.id)
                                "
                            >
                                {{ amenity.name }}
                            </button>
                        </div>
                    </div>

                    <Button type="submit">
                        <SearchIcon class="h-4 w-4" />
                        Search turfs
                    </Button>
                </div>
            </form>

            <section class="flex flex-col gap-4">
                <div
                    class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div>
                            <p
                                class="text-xs font-semibold tracking-[0.24em] text-slate-500 uppercase"
                            >
                                Search results
                            </p>
                            <h2
                                class="mt-1 text-xl font-semibold text-slate-950"
                            >
                                {{ results.meta.total }} turf<span
                                    v-if="results.meta.total !== 1"
                                    >s</span
                                >
                                matched
                            </h2>
                        </div>

                        <p class="text-sm text-slate-500">
                            Showing
                            {{ results.meta.from ?? 0 }}-{{
                                results.meta.to ?? 0
                            }}
                            of {{ results.meta.total }}
                        </p>

                        <div class="flex items-center gap-2">
                            <Label
                                for="results-sort"
                                class="text-xs font-medium text-slate-500"
                            >
                                Sort by
                            </Label>
                            <select
                                id="results-sort"
                                v-model="form.sort"
                                class="h-9 rounded-xl border border-slate-200 bg-white px-2 text-sm font-medium text-slate-700"
                                @change="submit"
                            >
                                <option
                                    v-for="sortOption in options.sorts"
                                    :key="`results-${sortOption.value}`"
                                    :value="sortOption.value"
                                    :disabled="
                                        sortOption.value === 'distance' &&
                                        (!form.latitude || !form.longitude)
                                    "
                                >
                                    {{ sortOption.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <p
                        v-if="
                            (form.sort === 'rating' && sortWarnings.rating) ||
                            (form.sort === 'popularity' &&
                                sortWarnings.popularity)
                        "
                        class="mt-3 text-xs leading-5 text-amber-700"
                    >
                        Rating and popularity sorting will become available as
                        reviews and booking-volume signals are introduced.
                    </p>
                </div>

                <div v-if="results.data.length" class="grid gap-4">
                    <article
                        v-for="result in results.data"
                        :key="result.id"
                        class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <div
                            class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                        >
                            <div class="space-y-3">
                                <div>
                                    <p
                                        class="text-xs font-semibold tracking-[0.24em] text-slate-500 uppercase"
                                    >
                                        {{ result.location.name }}
                                    </p>
                                    <h3
                                        class="mt-1 text-2xl font-semibold tracking-tight text-slate-950"
                                    >
                                        {{ result.name }}
                                    </h3>
                                </div>

                                <p class="text-sm leading-6 text-slate-600">
                                    {{
                                        result.description ??
                                        'Freshly published turf details are available on the detail page.'
                                    }}
                                </p>

                                <div
                                    class="flex flex-wrap items-center gap-3 text-sm text-slate-600"
                                >
                                    <span
                                        class="inline-flex items-center gap-1.5"
                                    >
                                        <MapPin class="h-4 w-4" />
                                        {{
                                            [
                                                result.location.locality,
                                                result.location.city,
                                                result.location.state,
                                            ]
                                                .filter(Boolean)
                                                .join(', ')
                                        }}
                                    </span>
                                    <span
                                        v-if="result.distance_meters !== null"
                                    >
                                        {{ result.distance_meters }} m away
                                    </span>
                                    <span>
                                        {{
                                            result.is_indoor === null
                                                ? 'Indoor/outdoor pending'
                                                : result.is_indoor
                                                  ? 'Indoor'
                                                  : 'Outdoor'
                                        }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="sport in result.sports"
                                        :key="sport.id"
                                        class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800"
                                    >
                                        {{ sport.name }}
                                    </span>
                                    <span
                                        v-for="amenity in result.amenities.slice(
                                            0,
                                            4,
                                        )"
                                        :key="`amenity-${amenity.id}`"
                                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"
                                    >
                                        {{ amenity.name }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="min-w-[16rem] rounded-[1.5rem] bg-slate-950 p-4 text-white"
                            >
                                <p
                                    class="text-xs font-semibold tracking-[0.24em] text-emerald-300 uppercase"
                                >
                                    Summary
                                </p>
                                <p class="mt-3 text-sm text-slate-300">
                                    Starting from
                                </p>
                                <p class="text-2xl font-semibold">
                                    {{
                                        result.pricing_summary.starting_price
                                            ? `${result.pricing_summary.currency} ${result.pricing_summary.starting_price}`
                                            : 'Pricing pending'
                                    }}
                                </p>

                                <div
                                    v-if="result.availability_summary"
                                    class="mt-4 rounded-2xl bg-white/10 p-3 text-sm"
                                >
                                    <p class="font-medium">
                                        {{
                                            result.availability_summary
                                                .has_availability
                                                ? `${result.availability_summary.available_slots_count} slots available`
                                                : 'No slots available for that date'
                                        }}
                                    </p>
                                    <p
                                        v-if="
                                            result.availability_summary
                                                .first_slot
                                        "
                                        class="mt-1 text-slate-300"
                                    >
                                        First slot:
                                        {{
                                            result.availability_summary
                                                .first_slot.starts_at_time
                                        }}
                                        -
                                        {{
                                            result.availability_summary
                                                .first_slot.ends_at_time
                                        }}
                                    </p>
                                </div>

                                <Button
                                    as-child
                                    class="mt-4 w-full bg-white text-slate-950 hover:bg-slate-100"
                                >
                                    <Link :href="result.detail_url">
                                        View details
                                        <ArrowRight class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </article>
                </div>

                <EmptyState
                    v-else
                    title="No matching turfs"
                    description="Try widening the location, distance, or price filters to reveal more venues."
                />

                <div
                    v-if="results.meta.last_page > 1"
                    class="flex items-center justify-between rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <Button
                        v-if="results.links.prev"
                        as-child
                        variant="outline"
                    >
                        <Link :href="results.links.prev">Previous</Link>
                    </Button>
                    <span v-else />

                    <p class="text-sm text-slate-500">
                        Page {{ results.meta.current_page }} of
                        {{ results.meta.last_page }}
                    </p>

                    <Button
                        v-if="results.links.next"
                        as-child
                        variant="outline"
                    >
                        <Link :href="results.links.next">Next</Link>
                    </Button>
                    <span v-else />
                </div>
            </section>
        </section>
    </main>
</template>
