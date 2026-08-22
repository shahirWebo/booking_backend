<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Clock3, ImagePlus, MapPinHouse, Plus, Save, Trash2 } from '@lucide/vue';
import FormFeedback from '@/components/feedback/FormFeedback.vue';
import InputError from '@/components/InputError.vue';
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

const props = defineProps<{
    mode: 'create' | 'edit';
    vendor: {
        id: number;
        display_name: string | null;
        legal_name: string | null;
    };
    location: LocationFormRecord | null;
    amenities: AmenityOption[];
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
    { value: 1, label: 'Monday' },
    { value: 2, label: 'Tuesday' },
    { value: 3, label: 'Wednesday' },
    { value: 4, label: 'Thursday' },
    { value: 5, label: 'Friday' },
    { value: 6, label: 'Saturday' },
    { value: 7, label: 'Sunday' },
];

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

function addImage(): void {
    form.images.push({
        file_id: 0,
        caption: '',
        alt_text: '',
    });
}

function removeImage(index: number): void {
    form.images.splice(index, 1);
}

function toggleAmenity(amenityId: number): void {
    if (form.amenity_ids.includes(amenityId)) {
        form.amenity_ids = form.amenity_ids.filter((id) => id !== amenityId);

        return;
    }

    form.amenity_ids = [...form.amenity_ids, amenityId];
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
                            {{ mode === 'create' ? 'Add location' : 'Edit location' }}
                        </h1>
                        <p
                            class="mt-2 max-w-3xl text-sm leading-6 text-sidebar-foreground/70"
                        >
                            Capture the venue address, weekly hours, amenities, and gallery references for
                            {{ vendor.display_name ?? vendor.legal_name ?? 'this vendor' }}.
                        </p>
                    </div>
                </div>

                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-background"
                >
                    <MapPinHouse class="h-5 w-5 text-muted-foreground" />
                </div>
            </div>
        </section>

        <section
            class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
        >
            <form class="space-y-6" @submit.prevent="submit">
                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="location-name">Location name</Label>
                        <Input id="location-name" v-model="form.name" name="name" />
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
                        <Label for="address-line-1">Address line 1</Label>
                        <Input
                            id="address-line-1"
                            v-model="form.address_line_1"
                            name="address_line_1"
                        />
                        <InputError :message="form.errors.address_line_1" />
                    </div>

                    <div class="grid gap-2 lg:col-span-2">
                        <Label for="address-line-2">Address line 2</Label>
                        <Input
                            id="address-line-2"
                            v-model="form.address_line_2"
                            name="address_line_2"
                        />
                        <InputError :message="form.errors.address_line_2" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="landmark">Landmark</Label>
                        <Input id="landmark" v-model="form.landmark" name="landmark" />
                        <InputError :message="form.errors.landmark" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="locality">Locality</Label>
                        <Input id="locality" v-model="form.locality" name="locality" />
                        <InputError :message="form.errors.locality" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="city">City</Label>
                        <Input id="city" v-model="form.city" name="city" />
                        <InputError :message="form.errors.city" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="state">State</Label>
                        <Input id="state" v-model="form.state" name="state" />
                        <InputError :message="form.errors.state" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="postal-code">Postal code</Label>
                        <Input
                            id="postal-code"
                            v-model="form.postal_code"
                            name="postal_code"
                        />
                        <InputError :message="form.errors.postal_code" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="country-code">Country code</Label>
                        <Input
                            id="country-code"
                            v-model="form.country_code"
                            name="country_code"
                            maxlength="2"
                        />
                        <InputError :message="form.errors.country_code" />
                    </div>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="latitude">Latitude</Label>
                        <Input id="latitude" v-model="form.latitude" name="latitude" />
                        <InputError :message="form.errors.latitude" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="longitude">Longitude</Label>
                        <Input id="longitude" v-model="form.longitude" name="longitude" />
                        <InputError :message="form.errors.longitude" />
                    </div>
                </div>

                <div class="space-y-4 rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold">Amenities</h2>
                            <p class="text-sm text-muted-foreground">
                                Select the shared amenities available at this venue.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <label
                            v-for="amenity in amenities"
                            :key="amenity.id"
                            class="flex items-center gap-3 rounded-2xl border border-sidebar-border/70 px-4 py-3 text-sm dark:border-sidebar-border"
                        >
                            <input
                                :checked="form.amenity_ids.includes(amenity.id)"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300"
                                @change="toggleAmenity(amenity.id)"
                            />
                            <span>
                                {{ amenity.name }}
                                <span class="text-muted-foreground">({{ amenity.code }})</span>
                            </span>
                        </label>
                    </div>
                    <InputError :message="form.errors.amenity_ids" />
                </div>

                <div class="space-y-4 rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="flex items-center gap-2 text-lg font-semibold">
                                <Clock3 class="h-4 w-4" />
                                Operating hours
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Add one or more weekday windows. Cross-midnight windows can end the next day.
                            </p>
                        </div>

                        <Button type="button" variant="outline" @click="addOperatingHour">
                            <Plus class="h-4 w-4" />
                            Add window
                        </Button>
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
                                <InputError :message="form.errors[`operating_hours.${index}.weekday`]" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`opens-${index}`">Opens</Label>
                                <Input
                                    :id="`opens-${index}`"
                                    v-model="window.opens_at_time"
                                    type="time"
                                />
                                <InputError :message="form.errors[`operating_hours.${index}.opens_at_time`]" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`closes-${index}`">Closes</Label>
                                <Input
                                    :id="`closes-${index}`"
                                    v-model="window.closes_at_time"
                                    type="time"
                                />
                                <InputError :message="form.errors[`operating_hours.${index}.closes_at_time`]" />
                            </div>

                            <div class="flex flex-col justify-between gap-3">
                                <label class="flex items-center gap-3 pt-7 text-sm text-muted-foreground">
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
                </div>

                <div class="space-y-4 rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="flex items-center gap-2 text-lg font-semibold">
                                <ImagePlus class="h-4 w-4" />
                                Location images
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Attach ready `location_image` file IDs owned by this vendor.
                            </p>
                        </div>

                        <Button type="button" variant="outline" @click="addImage">
                            <Plus class="h-4 w-4" />
                            Add image
                        </Button>
                    </div>

                    <div v-if="form.images.length" class="space-y-4">
                        <div
                            v-for="(image, index) in form.images"
                            :key="`image-${index}`"
                            class="grid gap-4 rounded-2xl border border-sidebar-border/70 p-4 md:grid-cols-3 dark:border-sidebar-border"
                        >
                            <div class="grid gap-2">
                                <Label :for="`image-file-${index}`">File ID</Label>
                                <Input
                                    :id="`image-file-${index}`"
                                    v-model.number="image.file_id"
                                    type="number"
                                />
                                <InputError :message="form.errors[`images.${index}.file_id`]" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`image-caption-${index}`">Caption</Label>
                                <Input
                                    :id="`image-caption-${index}`"
                                    v-model="image.caption"
                                />
                                <InputError :message="form.errors[`images.${index}.caption`]" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`image-alt-${index}`">Alt text</Label>
                                <Input
                                    :id="`image-alt-${index}`"
                                    v-model="image.alt_text"
                                />
                                <InputError :message="form.errors[`images.${index}.alt_text`]" />
                            </div>

                            <div class="md:col-span-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="border-red-200 text-red-700 hover:bg-red-50"
                                    @click="removeImage(index)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                    Remove image
                                </Button>
                            </div>
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
                        {{ mode === 'create' ? 'Create location' : 'Save changes' }}
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
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Location status</h2>
                    <p class="text-sm text-muted-foreground">
                        Toggle whether this location is currently active for vendor operations.
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
