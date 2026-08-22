<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    Clock3,
    ImagePlus,
    MapPinHouse,
    Plus,
    Save,
    Sparkles,
    Trash2,
} from '@lucide/vue';
import { computed } from 'vue';
import FormFeedback from '@/components/feedback/FormFeedback.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type LocationFormRecord = {
    id: number;
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
        file_id: number;
        caption: string | null;
        alt_text: string | null;
    }>;
};

type AmenityOption = {
    id: number;
    name: string;
    code: string;
};

type AvailableImage = {
    id: number;
    original_name: string | null;
    canonical_extension: string | null;
    size_bytes: number | null;
    status: string | null;
    attached_to_current_location: boolean;
};

const props = defineProps<{
    mode: 'create' | 'edit';
    vendor: {
        id: number;
        display_name: string | null;
        legal_name: string | null;
    };
    location: LocationFormRecord | null;
    amenities: AmenityOption[];
    available_images: AvailableImage[];
    routes: {
        index: string;
        submit: string;
        update_status?: string;
    };
}>();

const form = useForm({
    name: props.location?.name ?? '',
    address_line_1: props.location?.address_line_1 ?? '',
    address_line_2: props.location?.address_line_2 ?? '',
    landmark: props.location?.landmark ?? '',
    locality: props.location?.locality ?? '',
    city: props.location?.city ?? '',
    state: props.location?.state ?? '',
    postal_code: props.location?.postal_code ?? '',
    country_code: props.location?.country_code ?? 'IN',
    latitude: props.location?.latitude?.toString() ?? '',
    longitude: props.location?.longitude?.toString() ?? '',
    timezone: props.location?.timezone ?? 'Asia/Kolkata',
    amenity_ids: props.location?.amenity_ids ?? [],
    operating_hours:
        props.location?.operating_hours.map((hour) => ({
            weekday: hour.weekday,
            opens_at_time: hour.opens_at_time,
            closes_at_time: hour.closes_at_time,
            ends_next_day: hour.ends_next_day,
        })) ?? [],
    images:
        props.location?.images.map((image) => ({
            file_id: image.file_id,
            caption: image.caption ?? '',
            alt_text: image.alt_text ?? '',
        })) ?? [],
});

const weekdayOptions = [
    { value: 1, label: 'Monday', shortLabel: 'Mon' },
    { value: 2, label: 'Tuesday', shortLabel: 'Tue' },
    { value: 3, label: 'Wednesday', shortLabel: 'Wed' },
    { value: 4, label: 'Thursday', shortLabel: 'Thu' },
    { value: 5, label: 'Friday', shortLabel: 'Fri' },
    { value: 6, label: 'Saturday', shortLabel: 'Sat' },
    { value: 7, label: 'Sunday', shortLabel: 'Sun' },
];

const coordinateMapStyle =
    'relative h-56 overflow-hidden rounded-[28px] border border-sidebar-border/70 bg-[linear-gradient(180deg,rgba(191,219,254,0.95)_0%,rgba(224,242,254,0.92)_32%,rgba(253,230,138,0.9)_100%)] dark:border-sidebar-border';

const selectedAmenityOptions = computed(() =>
    props.amenities.filter((amenity) => form.amenity_ids.includes(amenity.id)),
);

const selectedLatitude = computed(() => parseCoordinate(form.latitude));
const selectedLongitude = computed(() => parseCoordinate(form.longitude));

const hasCoordinates = computed(
    () => selectedLatitude.value !== null && selectedLongitude.value !== null,
);

const mapMarkerStyle = computed(() => {
    if (!hasCoordinates.value) {
        return {
            left: '50%',
            top: '50%',
        };
    }

    return {
        left: `${((selectedLongitude.value ?? 0) + 180) / 3.6}%`,
        top: `${((90 - (selectedLatitude.value ?? 0)) / 180) * 100}%`,
    };
});

const groupedOperatingHours = computed(() =>
    weekdayOptions
        .map((weekday) => ({
            ...weekday,
            windows: form.operating_hours.filter(
                (window) => window.weekday === weekday.value,
            ),
        }))
        .filter((weekday) => weekday.windows.length > 0),
);

const imageLibrary = computed(() => {
    const known = new Map<number, AvailableImage>();

    props.available_images.forEach((image) => {
        known.set(image.id, image);
    });

    form.images.forEach((image) => {
        if (!known.has(image.file_id) && image.file_id > 0) {
            known.set(image.file_id, {
                id: image.file_id,
                original_name: `File #${image.file_id}`,
                canonical_extension: null,
                size_bytes: null,
                status: 'ready',
                attached_to_current_location: true,
            });
        }
    });

    return Array.from(known.values());
});

function submit(): void {
    if (props.mode === 'create') {
        form.post(props.routes.submit, {
            preserveScroll: true,
        });

        return;
    }

    form.put(props.routes.submit, {
        preserveScroll: true,
    });
}

function addOperatingHour(): void {
    form.operating_hours.push({
        weekday: 1,
        opens_at_time: '06:00',
        closes_at_time: '22:00',
        ends_next_day: false,
    });
}

function removeOperatingHour(index: number): void {
    form.operating_hours.splice(index, 1);
}

function toggleAmenity(amenityId: number): void {
    if (form.amenity_ids.includes(amenityId)) {
        form.amenity_ids = form.amenity_ids.filter((id) => id !== amenityId);

        return;
    }

    form.amenity_ids = [...form.amenity_ids, amenityId];
}

function toggleImageSelection(fileId: number): void {
    const existingIndex = form.images.findIndex(
        (image) => image.file_id === fileId,
    );

    if (existingIndex >= 0) {
        form.images.splice(existingIndex, 1);

        return;
    }

    form.images.push({
        file_id: fileId,
        caption: '',
        alt_text: '',
    });
}

function updateStatus(status: 'active' | 'inactive'): void {
    if (!props.routes.update_status) {
        return;
    }

    router.post(
        props.routes.update_status,
        { status },
        {
            preserveScroll: true,
        },
    );
}

function pickCoordinates(event: MouseEvent): void {
    const element = event.currentTarget;

    if (!(element instanceof HTMLElement)) {
        return;
    }

    const bounds = element.getBoundingClientRect();
    const relativeX = clamp((event.clientX - bounds.left) / bounds.width, 0, 1);
    const relativeY = clamp((event.clientY - bounds.top) / bounds.height, 0, 1);

    const longitude = -180 + relativeX * 360;
    const latitude = 90 - relativeY * 180;

    form.latitude = latitude.toFixed(6);
    form.longitude = longitude.toFixed(6);
}

function nudgeCoordinate(
    axis: 'latitude' | 'longitude',
    nextValue: string,
): void {
    if (axis === 'latitude') {
        form.latitude = Number(nextValue).toFixed(6);

        return;
    }

    form.longitude = Number(nextValue).toFixed(6);
}

function clearCoordinates(): void {
    form.latitude = '';
    form.longitude = '';
}

function isImageSelected(fileId: number): boolean {
    return form.images.some((image) => image.file_id === fileId);
}

function imageRecord(fileId: number) {
    return imageLibrary.value.find((image) => image.id === fileId) ?? null;
}

function formatWindow(window: {
    opens_at_time: string;
    closes_at_time: string;
    ends_next_day: boolean;
}): string {
    return `${window.opens_at_time} - ${window.closes_at_time}${window.ends_next_day ? ' next day' : ''}`;
}

function fileBadge(image: AvailableImage): string {
    if (image.attached_to_current_location) {
        return 'Attached';
    }

    return 'Ready';
}

function fileSubtitle(image: AvailableImage): string {
    const parts = [];

    if (image.canonical_extension) {
        parts.push(image.canonical_extension.toUpperCase());
    }

    if (image.size_bytes !== null) {
        parts.push(formatBytes(image.size_bytes));
    }

    if (parts.length === 0) {
        return `File #${image.id}`;
    }

    return `${parts.join(' • ')} • #${image.id}`;
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function parseCoordinate(value: string): number | null {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : null;
}

function clamp(value: number, min: number, max: number): number {
    return Math.min(max, Math.max(min, value));
}
</script>

<template>
    <Head :title="mode === 'create' ? 'Add Location' : 'Edit Location'" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <section
            class="overflow-hidden rounded-3xl border border-sidebar-border/70 bg-sidebar-accent/40 p-5 dark:border-sidebar-border"
        >
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
            >
                <div class="space-y-3">
                    <Link
                        :href="routes.index"
                        class="inline-flex items-center gap-2 text-sm font-medium text-sidebar-foreground/70 transition hover:text-sidebar-foreground"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Back to locations
                    </Link>
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.24em] text-sidebar-foreground/60 uppercase"
                        >
                            Vendor operations
                        </p>
                        <h1
                            class="mt-2 text-2xl font-semibold tracking-tight text-sidebar-foreground"
                        >
                            {{
                                mode === 'create'
                                    ? 'Add location'
                                    : 'Edit location'
                            }}
                        </h1>
                        <p
                            class="mt-2 max-w-3xl text-sm leading-6 text-sidebar-foreground/70"
                        >
                            Capture the venue address, weekly hours, amenities,
                            and gallery references for
                            {{
                                vendor.display_name ??
                                vendor.legal_name ??
                                'this vendor'
                            }}.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div
                        class="rounded-2xl bg-background px-4 py-3 text-center"
                    >
                        <p
                            class="text-[11px] font-semibold text-muted-foreground uppercase"
                        >
                            Hours
                        </p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ form.operating_hours.length }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-background px-4 py-3 text-center"
                    >
                        <p
                            class="text-[11px] font-semibold text-muted-foreground uppercase"
                        >
                            Amenities
                        </p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ form.amenity_ids.length }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-background px-4 py-3 text-center"
                    >
                        <p
                            class="text-[11px] font-semibold text-muted-foreground uppercase"
                        >
                            Images
                        </p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ form.images.length }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-background px-4 py-3 text-center"
                    >
                        <p
                            class="text-[11px] font-semibold text-muted-foreground uppercase"
                        >
                            Map
                        </p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ hasCoordinates ? 'Set' : 'Open' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
        >
            <form class="space-y-6" @submit.prevent="submit">
                <div class="grid gap-5 lg:grid-cols-[1.15fr_0.85fr]">
                    <div class="space-y-5">
                        <div class="grid gap-5 lg:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="location-name">Location name</Label>
                                <Input
                                    id="location-name"
                                    v-model="form.name"
                                    name="name"
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="location-timezone">Timezone</Label>
                                <Input
                                    id="location-timezone"
                                    v-model="form.timezone"
                                    name="timezone"
                                />
                                <InputError :message="form.errors.timezone" />
                            </div>
                        </div>

                        <div class="grid gap-5 lg:grid-cols-2">
                            <div class="grid gap-2 lg:col-span-2">
                                <Label for="address-line-1"
                                    >Address line 1</Label
                                >
                                <Input
                                    id="address-line-1"
                                    v-model="form.address_line_1"
                                    name="address_line_1"
                                />
                                <InputError
                                    :message="form.errors.address_line_1"
                                />
                            </div>

                            <div class="grid gap-2 lg:col-span-2">
                                <Label for="address-line-2"
                                    >Address line 2</Label
                                >
                                <Input
                                    id="address-line-2"
                                    v-model="form.address_line_2"
                                    name="address_line_2"
                                />
                                <InputError
                                    :message="form.errors.address_line_2"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="landmark">Landmark</Label>
                                <Input
                                    id="landmark"
                                    v-model="form.landmark"
                                    name="landmark"
                                />
                                <InputError :message="form.errors.landmark" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="locality">Locality</Label>
                                <Input
                                    id="locality"
                                    v-model="form.locality"
                                    name="locality"
                                />
                                <InputError :message="form.errors.locality" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="city">City</Label>
                                <Input
                                    id="city"
                                    v-model="form.city"
                                    name="city"
                                />
                                <InputError :message="form.errors.city" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="state">State</Label>
                                <Input
                                    id="state"
                                    v-model="form.state"
                                    name="state"
                                />
                                <InputError :message="form.errors.state" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="postal-code">Postal code</Label>
                                <Input
                                    id="postal-code"
                                    v-model="form.postal_code"
                                    name="postal_code"
                                />
                                <InputError
                                    :message="form.errors.postal_code"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="country-code">Country code</Label>
                                <Input
                                    id="country-code"
                                    v-model="form.country_code"
                                    name="country_code"
                                    maxlength="2"
                                />
                                <InputError
                                    :message="form.errors.country_code"
                                />
                            </div>
                        </div>
                    </div>

                    <aside
                        class="space-y-4 rounded-[28px] border border-sidebar-border/70 bg-muted/20 p-4 dark:border-sidebar-border"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-[0.24em] text-muted-foreground uppercase"
                                >
                                    Quick summary
                                </p>
                                <h2 class="mt-2 text-lg font-semibold">
                                    Launch-ready checklist
                                </h2>
                            </div>
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-background"
                            >
                                <MapPinHouse
                                    class="h-5 w-5 text-muted-foreground"
                                />
                            </div>
                        </div>

                        <div class="grid gap-3">
                            <div class="rounded-2xl bg-background px-4 py-3">
                                <p class="text-sm font-medium">Address</p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ form.city || 'Add city' }},
                                    {{ form.state || 'state' }}
                                </p>
                            </div>
                            <div class="rounded-2xl bg-background px-4 py-3">
                                <p class="text-sm font-medium">Coordinates</p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{
                                        hasCoordinates
                                            ? `${selectedLatitude?.toFixed(4)}, ${selectedLongitude?.toFixed(4)}`
                                            : 'Tap the map to set the venue pin'
                                    }}
                                </p>
                            </div>
                            <div class="rounded-2xl bg-background px-4 py-3">
                                <p class="text-sm font-medium">Amenities</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <Badge
                                        v-for="amenity in selectedAmenityOptions"
                                        :key="amenity.id"
                                        variant="secondary"
                                        class="rounded-full"
                                    >
                                        {{ amenity.name }}
                                    </Badge>
                                    <span
                                        v-if="
                                            selectedAmenityOptions.length === 0
                                        "
                                        class="text-sm text-muted-foreground"
                                    >
                                        Select the venue basics your customers
                                        expect.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>

                <div
                    class="space-y-4 rounded-[28px] border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <h2
                                class="flex items-center gap-2 text-lg font-semibold"
                            >
                                <MapPinHouse class="h-4 w-4" />
                                Map and coordinates
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Tap anywhere on the map, then fine-tune latitude
                                and longitude below.
                            </p>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            @click="clearCoordinates"
                        >
                            Clear coordinates
                        </Button>
                    </div>

                    <button
                        type="button"
                        :class="coordinateMapStyle"
                        data-test="coordinate-map"
                        @click="pickCoordinates"
                    >
                        <div
                            class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.65),transparent_30%),radial-gradient(circle_at_72%_68%,rgba(14,165,233,0.18),transparent_35%)]"
                        />
                        <div
                            v-for="line in [25, 50, 75]"
                            :key="line"
                            class="absolute inset-x-0 border-t border-white/50"
                            :style="{ top: `${line}%` }"
                        />
                        <div
                            v-for="line in [25, 50, 75]"
                            :key="`vertical-${line}`"
                            class="absolute inset-y-0 border-l border-white/50"
                            :style="{ left: `${line}%` }"
                        />
                        <div
                            class="absolute top-[34%] left-[17%] h-20 w-24 rounded-full bg-emerald-700/10 blur-2xl"
                        />
                        <div
                            class="absolute top-[40%] left-[54%] h-24 w-28 rounded-full bg-emerald-700/10 blur-2xl"
                        />
                        <div
                            class="absolute top-[62%] left-[76%] h-14 w-14 rounded-full bg-amber-500/10 blur-xl"
                        />
                        <div
                            class="absolute -translate-x-1/2 -translate-y-1/2 transition-all"
                            :style="mapMarkerStyle"
                        >
                            <div class="flex flex-col items-center">
                                <div
                                    class="rounded-full bg-slate-900 p-2 text-white shadow-lg ring-4 ring-white/70"
                                >
                                    <MapPinHouse class="h-4 w-4" />
                                </div>
                                <div
                                    class="mt-1 rounded-full bg-white/90 px-2 py-1 text-[11px] font-semibold text-slate-700"
                                >
                                    {{
                                        hasCoordinates ? 'Pinned' : 'Tap to set'
                                    }}
                                </div>
                            </div>
                        </div>
                    </button>

                    <div class="grid gap-5 lg:grid-cols-2">
                        <div class="space-y-3">
                            <div class="grid gap-2">
                                <Label for="latitude">Latitude</Label>
                                <Input
                                    id="latitude"
                                    v-model="form.latitude"
                                    name="latitude"
                                />
                                <InputError :message="form.errors.latitude" />
                            </div>
                            <input
                                :value="selectedLatitude ?? 0"
                                type="range"
                                min="-90"
                                max="90"
                                step="0.000001"
                                class="w-full accent-slate-900"
                                @input="
                                    nudgeCoordinate(
                                        'latitude',
                                        ($event.target as HTMLInputElement)
                                            .value,
                                    )
                                "
                            />
                        </div>

                        <div class="space-y-3">
                            <div class="grid gap-2">
                                <Label for="longitude">Longitude</Label>
                                <Input
                                    id="longitude"
                                    v-model="form.longitude"
                                    name="longitude"
                                />
                                <InputError :message="form.errors.longitude" />
                            </div>
                            <input
                                :value="selectedLongitude ?? 0"
                                type="range"
                                min="-180"
                                max="180"
                                step="0.000001"
                                class="w-full accent-slate-900"
                                @input="
                                    nudgeCoordinate(
                                        'longitude',
                                        ($event.target as HTMLInputElement)
                                            .value,
                                    )
                                "
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="space-y-4 rounded-[28px] border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2
                                class="flex items-center gap-2 text-lg font-semibold"
                            >
                                <Sparkles class="h-4 w-4" />
                                Amenities
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Choose the venue comforts you want surfaced in
                                the vendor workflow.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <button
                            v-for="amenity in amenities"
                            :key="amenity.id"
                            type="button"
                            class="rounded-2xl border px-4 py-4 text-left transition"
                            :class="
                                form.amenity_ids.includes(amenity.id)
                                    ? 'border-slate-900 bg-slate-900 text-white shadow-lg shadow-slate-900/10'
                                    : 'border-sidebar-border/70 bg-background hover:border-slate-400 dark:border-sidebar-border'
                            "
                            @click="toggleAmenity(amenity.id)"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium">
                                        {{ amenity.name }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs uppercase"
                                        :class="
                                            form.amenity_ids.includes(
                                                amenity.id,
                                            )
                                                ? 'text-white/75'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{ amenity.code }}
                                    </p>
                                </div>
                                <div
                                    class="flex h-6 w-6 items-center justify-center rounded-full border"
                                    :class="
                                        form.amenity_ids.includes(amenity.id)
                                            ? 'border-white/50 bg-white/15'
                                            : 'border-sidebar-border/70'
                                    "
                                >
                                    <Check
                                        v-if="
                                            form.amenity_ids.includes(
                                                amenity.id,
                                            )
                                        "
                                        class="h-4 w-4"
                                    />
                                </div>
                            </div>
                        </button>
                    </div>
                    <InputError :message="form.errors.amenity_ids" />
                </div>

                <div
                    class="space-y-4 rounded-[28px] border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2
                                class="flex items-center gap-2 text-lg font-semibold"
                            >
                                <Clock3 class="h-4 w-4" />
                                Operating hours
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Add one or more weekday windows. Cross-midnight
                                windows can end the next day.
                            </p>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            @click="addOperatingHour"
                        >
                            <Plus class="h-4 w-4" />
                            Add window
                        </Button>
                    </div>

                    <div
                        v-if="groupedOperatingHours.length"
                        class="grid gap-3 lg:grid-cols-2"
                    >
                        <div
                            v-for="weekday in groupedOperatingHours"
                            :key="weekday.value"
                            class="rounded-2xl bg-muted/30 px-4 py-3"
                        >
                            <p class="text-sm font-semibold">
                                {{ weekday.label }}
                            </p>
                            <p class="mt-2 text-sm text-muted-foreground">
                                {{
                                    weekday.windows
                                        .map((window) => formatWindow(window))
                                        .join(' • ')
                                }}
                            </p>
                        </div>
                    </div>

                    <div v-if="form.operating_hours.length" class="space-y-4">
                        <div
                            v-for="(window, index) in form.operating_hours"
                            :key="`window-${index}`"
                            class="grid gap-4 rounded-2xl border border-sidebar-border/70 p-4 md:grid-cols-4 dark:border-sidebar-border"
                        >
                            <div class="grid gap-2">
                                <Label :for="`weekday-${index}`">Weekday</Label>
                                <select
                                    :id="`weekday-${index}`"
                                    v-model.number="window.weekday"
                                    class="rounded-[var(--radius-control)] border border-input bg-transparent px-4 py-3 text-sm"
                                >
                                    <option
                                        v-for="option in weekdayOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <InputError
                                    :message="
                                        form.errors[
                                            `operating_hours.${index}.weekday`
                                        ]
                                    "
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`opens-${index}`">Opens</Label>
                                <Input
                                    :id="`opens-${index}`"
                                    v-model="window.opens_at_time"
                                    type="time"
                                />
                                <InputError
                                    :message="
                                        form.errors[
                                            `operating_hours.${index}.opens_at_time`
                                        ]
                                    "
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`closes-${index}`">Closes</Label>
                                <Input
                                    :id="`closes-${index}`"
                                    v-model="window.closes_at_time"
                                    type="time"
                                />
                                <InputError
                                    :message="
                                        form.errors[
                                            `operating_hours.${index}.closes_at_time`
                                        ]
                                    "
                                />
                            </div>

                            <div class="flex flex-col justify-between gap-3">
                                <label
                                    class="flex items-center gap-3 pt-7 text-sm text-muted-foreground"
                                >
                                    <input
                                        v-model="window.ends_next_day"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300"
                                    />
                                    Ends next day
                                </label>

                                <Button
                                    type="button"
                                    variant="outline"
                                    class="border-red-200 text-red-700 hover:bg-red-50"
                                    @click="removeOperatingHour(index)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                    Remove
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl border border-dashed border-sidebar-border/70 px-4 py-6 text-sm text-muted-foreground"
                    >
                        Start with your first weekday window so customers and
                        staff know when the venue is open.
                    </div>
                </div>

                <div
                    class="space-y-4 rounded-[28px] border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2
                                class="flex items-center gap-2 text-lg font-semibold"
                            >
                                <ImagePlus class="h-4 w-4" />
                                Location gallery
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Choose from ready `location_image` files already
                                owned by this vendor.
                            </p>
                        </div>

                        <Badge variant="secondary" class="rounded-full">
                            {{ form.images.length }} selected
                        </Badge>
                    </div>

                    <div
                        v-if="imageLibrary.length"
                        class="grid gap-3 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <button
                            v-for="image in imageLibrary"
                            :key="image.id"
                            :data-test="`image-library-${image.id}`"
                            type="button"
                            class="rounded-[24px] border p-3 text-left transition"
                            :class="
                                isImageSelected(image.id)
                                    ? 'border-slate-900 bg-slate-900 text-white shadow-lg shadow-slate-900/10'
                                    : 'border-sidebar-border/70 bg-background hover:border-slate-400 dark:border-sidebar-border'
                            "
                            @click="toggleImageSelection(image.id)"
                        >
                            <div
                                class="flex aspect-[4/3] items-end rounded-[20px] p-3"
                                :class="
                                    isImageSelected(image.id)
                                        ? 'bg-[linear-gradient(160deg,#334155,#0f172a)]'
                                        : 'bg-[linear-gradient(160deg,#e2e8f0,#f8fafc)]'
                                "
                            >
                                <Badge
                                    variant="secondary"
                                    class="rounded-full"
                                    :class="
                                        isImageSelected(image.id)
                                            ? 'bg-white/10 text-white'
                                            : ''
                                    "
                                >
                                    {{ fileBadge(image) }}
                                </Badge>
                            </div>

                            <div
                                class="mt-3 flex items-start justify-between gap-3"
                            >
                                <div>
                                    <p class="font-medium">
                                        {{
                                            image.original_name ??
                                            `File #${image.id}`
                                        }}
                                    </p>
                                    <p
                                        class="mt-1 text-sm"
                                        :class="
                                            isImageSelected(image.id)
                                                ? 'text-white/70'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{ fileSubtitle(image) }}
                                    </p>
                                </div>
                                <div
                                    class="flex h-7 w-7 items-center justify-center rounded-full border"
                                    :class="
                                        isImageSelected(image.id)
                                            ? 'border-white/50 bg-white/15'
                                            : 'border-sidebar-border/70'
                                    "
                                >
                                    <Check
                                        v-if="isImageSelected(image.id)"
                                        class="h-4 w-4"
                                    />
                                </div>
                            </div>
                        </button>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl border border-dashed border-sidebar-border/70 px-4 py-6 text-sm text-muted-foreground"
                    >
                        No ready location-image files are available yet for this
                        vendor.
                    </div>

                    <div v-if="form.images.length" class="space-y-4">
                        <div
                            v-for="(image, index) in form.images"
                            :key="`selected-image-${image.file_id}`"
                            class="rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        >
                            <div
                                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div>
                                    <p class="font-medium">
                                        {{
                                            imageRecord(image.file_id)
                                                ?.original_name ??
                                            `File #${image.file_id}`
                                        }}
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        {{
                                            imageRecord(image.file_id)
                                                ? fileSubtitle(
                                                      imageRecord(
                                                          image.file_id,
                                                      )!,
                                                  )
                                                : `File #${image.file_id}`
                                        }}
                                    </p>
                                </div>

                                <Button
                                    type="button"
                                    variant="outline"
                                    class="border-red-200 text-red-700 hover:bg-red-50"
                                    @click="toggleImageSelection(image.file_id)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                    Remove image
                                </Button>
                            </div>

                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label :for="`image-caption-${index}`"
                                        >Caption</Label
                                    >
                                    <Input
                                        :id="`image-caption-${index}`"
                                        v-model="image.caption"
                                    />
                                    <InputError
                                        :message="
                                            form.errors[
                                                `images.${index}.caption`
                                            ]
                                        "
                                    />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`image-alt-${index}`"
                                        >Alt text</Label
                                    >
                                    <Input
                                        :id="`image-alt-${index}`"
                                        v-model="image.alt_text"
                                    />
                                    <InputError
                                        :message="
                                            form.errors[
                                                `images.${index}.alt_text`
                                            ]
                                        "
                                    />
                                </div>
                            </div>
                            <InputError
                                :message="
                                    form.errors[`images.${index}.file_id`]
                                "
                            />
                        </div>
                    </div>
                </div>

                <FormFeedback
                    v-if="form.hasErrors"
                    message="Please fix the highlighted location fields and try again."
                    variant="error"
                />

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Button type="submit" :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        {{
                            mode === 'create'
                                ? 'Create location'
                                : 'Save changes'
                        }}
                    </Button>
                    <Button as-child type="button" variant="outline">
                        <Link :href="routes.index">Cancel</Link>
                    </Button>
                </div>
            </form>
        </section>

        <section
            v-if="mode === 'edit' && location"
            class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="text-lg font-semibold">Location status</h2>
                    <p class="text-sm text-muted-foreground">
                        Toggle whether this location is currently active for
                        vendor operations.
                    </p>
                </div>

                <div class="flex gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="location.status === 'active'"
                        @click="updateStatus('active')"
                    >
                        Activate
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="location.status === 'inactive'"
                        @click="updateStatus('inactive')"
                    >
                        Deactivate
                    </Button>
                </div>
            </div>
        </section>
    </div>
</template>
