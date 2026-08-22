<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    CircleDot,
    ImagePlus,
    Plus,
    Save,
    Settings2,
    Trash2,
} from '@lucide/vue';
import { computed } from 'vue';
import FormFeedback from '@/components/feedback/FormFeedback.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Option = { id: number; name: string; code: string };
type Image = { file_id: number; caption: string; alt_text: string };
type Rule = { title: string; description: string; is_active: boolean };
type Turf = {
    id: number;
    name: string;
    description: string | null;
    status: 'active' | 'inactive';
    surface_type: string | null;
    is_indoor: boolean;
    capacity_count: number | null;
    length_meters: number | null;
    width_meters: number | null;
    sport_ids: number[];
    amenity_ids: number[];
    images: Array<Image & { id: number }>;
    rules: Array<Rule & { id: number }>;
};
type LibraryImage = {
    id: number;
    original_name: string | null;
    canonical_extension: string | null;
    size_bytes: number | null;
    attached_to_current_turf: boolean;
};

const props = defineProps<{
    mode: 'create' | 'edit';
    vendor: {
        id: number;
        display_name: string | null;
        legal_name: string | null;
    };
    location: {
        id: number;
        name: string;
        city: string;
        state: string;
        status: string;
    };
    turf: Turf | null;
    sports: Option[];
    amenities: Option[];
    available_images: LibraryImage[];
    routes: {
        index: string;
        submit: string;
        update_status?: string;
        location_edit: string;
    };
}>();

const form = useForm({
    name: props.turf?.name ?? '',
    description: props.turf?.description ?? '',
    surface_type: props.turf?.surface_type ?? '',
    is_indoor: props.turf?.is_indoor ?? false,
    capacity_count: props.turf?.capacity_count?.toString() ?? '',
    length_meters: props.turf?.length_meters?.toString() ?? '',
    width_meters: props.turf?.width_meters?.toString() ?? '',
    sport_ids: props.turf?.sport_ids ?? [],
    amenity_ids: props.turf?.amenity_ids ?? [],
    images:
        props.turf?.images.map(({ file_id, caption, alt_text }) => ({
            file_id,
            caption: caption ?? '',
            alt_text: alt_text ?? '',
        })) ?? [],
    rules:
        props.turf?.rules.map(({ title, description, is_active }) => ({
            title,
            description,
            is_active,
        })) ?? [],
});

const selectedSports = computed(() =>
    props.sports.filter((item) => form.sport_ids.includes(item.id)),
);
const selectedAmenities = computed(() =>
    props.amenities.filter((item) => form.amenity_ids.includes(item.id)),
);
const imageLibrary = computed(() => {
    const files = new Map(
        props.available_images.map((file) => [file.id, file]),
    );
    form.images.forEach((image) =>
        files.set(
            image.file_id,
            files.get(image.file_id) ?? {
                id: image.file_id,
                original_name: `File #${image.file_id}`,
                canonical_extension: null,
                size_bytes: null,
                attached_to_current_turf: true,
            },
        ),
    );

    return [...files.values()];
});

function submit(): void {
    if (props.mode === 'create') {
        form.post(props.routes.submit, { preserveScroll: true });

        return;
    }

    form.put(props.routes.submit, { preserveScroll: true });
}

function toggle(key: 'sport_ids' | 'amenity_ids', id: number): void {
    form[key] = form[key].includes(id)
        ? form[key].filter((value) => value !== id)
        : [...form[key], id];
}
function toggleImage(fileId: number): void {
    const index = form.images.findIndex((image) => image.file_id === fileId);

    if (index >= 0) {
        form.images.splice(index, 1);

        return;
    }

    form.images.push({ file_id: fileId, caption: '', alt_text: '' });
}
function selectedImage(fileId: number): boolean {
    return form.images.some((image) => image.file_id === fileId);
}
function addRule(): void {
    form.rules.push({ title: '', description: '', is_active: true });
}
function removeRule(index: number): void {
    form.rules.splice(index, 1);
}
function updateStatus(status: 'active' | 'inactive'): void {
    if (props.routes.update_status) {
        router.post(
            props.routes.update_status,
            { status },
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head :title="mode === 'create' ? 'Add Turf' : 'Edit Turf'" />
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <section
            class="overflow-hidden rounded-3xl border border-sidebar-border/70 bg-sidebar-accent/40 p-5 dark:border-sidebar-border"
        >
            <div
                class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"
            >
                <div>
                    <Link
                        :href="routes.index"
                        class="inline-flex items-center gap-2 text-sm font-medium text-sidebar-foreground/70 hover:text-sidebar-foreground"
                        ><ArrowLeft class="h-4 w-4" /> Back to turfs</Link
                    >
                    <p
                        class="mt-5 text-xs font-semibold tracking-[0.24em] text-sidebar-foreground/60 uppercase"
                    >
                        {{ location.name }} · {{ location.city }}
                    </p>
                    <h1
                        class="mt-2 text-2xl font-semibold tracking-tight text-sidebar-foreground"
                    >
                        {{ mode === 'create' ? 'Add a turf' : 'Edit turf' }}
                    </h1>
                    <p
                        class="mt-2 max-w-2xl text-sm leading-6 text-sidebar-foreground/70"
                    >
                        Set up the playing surface, supported sports, amenities,
                        gallery, and ground rules.
                    </p>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div
                        class="rounded-2xl bg-background px-4 py-3 text-center"
                    >
                        <p
                            class="text-[11px] font-semibold text-muted-foreground uppercase"
                        >
                            Sports
                        </p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ form.sport_ids.length }}
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
                            Rules
                        </p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ form.rules.length }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <form class="space-y-4" @submit.prevent="submit">
            <FormFeedback
                v-if="form.hasErrors"
                variant="error"
                message="Please review the highlighted fields before saving."
            />
            <section
                class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div class="flex items-center gap-2">
                    <Settings2 class="h-5 w-5 text-muted-foreground" />
                    <div>
                        <h2 class="text-lg font-semibold">Turf details</h2>
                        <p class="text-sm text-muted-foreground">
                            Give players the key information at a glance.
                        </p>
                    </div>
                </div>
                <div class="mt-5 grid gap-5 lg:grid-cols-2">
                    <div class="grid gap-2 lg:col-span-2">
                        <Label for="turf-name">Turf name</Label
                        ><Input
                            id="turf-name"
                            v-model="form.name"
                            name="name"
                            placeholder="e.g. Arena One"
                        /><InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="surface-type">Surface type</Label
                        ><Input
                            id="surface-type"
                            v-model="form.surface_type"
                            name="surface_type"
                            placeholder="e.g. Artificial grass"
                        /><InputError :message="form.errors.surface_type" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="capacity-count">Player capacity</Label
                        ><Input
                            id="capacity-count"
                            v-model="form.capacity_count"
                            name="capacity_count"
                            type="number"
                            min="1"
                            placeholder="e.g. 10"
                        /><InputError :message="form.errors.capacity_count" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="length-meters">Length (metres)</Label
                        ><Input
                            id="length-meters"
                            v-model="form.length_meters"
                            name="length_meters"
                            type="number"
                            min="0"
                            step="0.01"
                        /><InputError :message="form.errors.length_meters" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="width-meters">Width (metres)</Label
                        ><Input
                            id="width-meters"
                            v-model="form.width_meters"
                            name="width_meters"
                            type="number"
                            min="0"
                            step="0.01"
                        /><InputError :message="form.errors.width_meters" />
                    </div>
                    <div class="grid gap-2 lg:col-span-2">
                        <Label for="description">Description</Label
                        ><textarea
                            id="description"
                            v-model="form.description"
                            name="description"
                            rows="4"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Describe the surface, lighting, and anything players should know."
                        /><InputError :message="form.errors.description" />
                    </div>
                </div>
                <label
                    class="mt-5 flex cursor-pointer items-center justify-between rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    ><span
                        ><span class="block font-medium">Indoor turf</span
                        ><span class="mt-1 block text-sm text-muted-foreground"
                            >This surface is protected from weather.</span
                        ></span
                    ><input
                        v-model="form.is_indoor"
                        type="checkbox"
                        class="h-5 w-5 accent-slate-900"
                /></label>
            </section>

            <section
                class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <h2 class="text-lg font-semibold">Sports and amenities</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Choose what this specific turf supports.
                </p>
                <div class="mt-5 grid gap-6 lg:grid-cols-2">
                    <div>
                        <h3 class="text-sm font-semibold">Supported sports</h3>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                v-for="sport in sports"
                                :key="sport.id"
                                type="button"
                                class="rounded-full border px-3 py-2 text-sm font-medium transition"
                                :class="
                                    form.sport_ids.includes(sport.id)
                                        ? 'border-slate-900 bg-slate-900 text-white'
                                        : 'border-sidebar-border bg-background hover:bg-muted'
                                "
                                @click="toggle('sport_ids', sport.id)"
                            >
                                <Check
                                    v-if="form.sport_ids.includes(sport.id)"
                                    class="mr-1 inline h-3.5 w-3.5"
                                />{{ sport.name }}
                            </button>
                            <p
                                v-if="sports.length === 0"
                                class="text-sm text-muted-foreground"
                            >
                                No active sports are available yet.
                            </p>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold">Turf amenities</h3>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                v-for="amenity in amenities"
                                :key="amenity.id"
                                type="button"
                                class="rounded-full border px-3 py-2 text-sm font-medium transition"
                                :class="
                                    form.amenity_ids.includes(amenity.id)
                                        ? 'border-emerald-700 bg-emerald-700 text-white'
                                        : 'border-sidebar-border bg-background hover:bg-muted'
                                "
                                @click="toggle('amenity_ids', amenity.id)"
                            >
                                <Check
                                    v-if="form.amenity_ids.includes(amenity.id)"
                                    class="mr-1 inline h-3.5 w-3.5"
                                />{{ amenity.name }}
                            </button>
                            <p
                                v-if="amenities.length === 0"
                                class="text-sm text-muted-foreground"
                            >
                                No active amenities are available yet.
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    v-if="selectedSports.length || selectedAmenities.length"
                    class="mt-5 rounded-2xl bg-muted/40 p-4 text-sm text-muted-foreground"
                >
                    <span v-if="selectedSports.length">{{
                        selectedSports.map((item) => item.name).join(', ')
                    }}</span
                    ><span
                        v-if="selectedSports.length && selectedAmenities.length"
                    >
                        · </span
                    ><span v-if="selectedAmenities.length">{{
                        selectedAmenities.map((item) => item.name).join(', ')
                    }}</span>
                </div>
            </section>

            <section
                class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2
                            class="flex items-center gap-2 text-lg font-semibold"
                        >
                            <ImagePlus class="h-5 w-5" />Gallery
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Attach ready turf images from the vendor library.
                        </p>
                    </div>
                    <Badge variant="secondary" class="rounded-full"
                        >{{ form.images.length }} selected</Badge
                    >
                </div>
                <InputError class="mt-3" :message="form.errors.images" />
                <div
                    v-if="imageLibrary.length"
                    class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3"
                >
                    <button
                        v-for="image in imageLibrary"
                        :key="image.id"
                        type="button"
                        class="rounded-2xl border p-4 text-left transition"
                        :class="
                            selectedImage(image.id)
                                ? 'border-slate-900 bg-slate-900 text-white'
                                : 'border-sidebar-border hover:bg-muted/50'
                        "
                        @click="toggleImage(image.id)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <ImagePlus class="h-5 w-5 shrink-0" /><Check
                                v-if="selectedImage(image.id)"
                                class="h-5 w-5 shrink-0"
                            />
                        </div>
                        <p class="mt-4 truncate text-sm font-semibold">
                            {{ image.original_name ?? `File #${image.id}` }}
                        </p>
                        <p class="mt-1 text-xs opacity-70">
                            {{
                                image.canonical_extension?.toUpperCase() ??
                                'READY FILE'
                            }}
                        </p>
                    </button>
                </div>
                <div
                    v-else
                    class="mt-5 rounded-2xl border border-dashed border-sidebar-border p-5 text-sm text-muted-foreground"
                >
                    No ready turf images are available. Upload images to the
                    vendor gallery first, then return here to attach them.
                </div>
                <div v-if="form.images.length" class="mt-5 grid gap-3">
                    <div
                        v-for="(image, index) in form.images"
                        :key="image.file_id"
                        class="grid gap-3 rounded-2xl bg-muted/40 p-4 lg:grid-cols-[1fr_1fr_1fr]"
                    >
                        <p class="self-center text-sm font-medium">
                            {{
                                imageLibrary.find(
                                    (file) => file.id === image.file_id,
                                )?.original_name ?? `File #${image.file_id}`
                            }}
                        </p>
                        <Input
                            v-model="image.caption"
                            :name="`images.${index}.caption`"
                            placeholder="Caption (optional)"
                        /><Input
                            v-model="image.alt_text"
                            :name="`images.${index}.alt_text`"
                            placeholder="Alt text (optional)"
                        />
                    </div>
                </div>
            </section>

            <section
                class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">Ground rules</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Add clear rules that players see before booking.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addRule"
                        ><Plus class="h-4 w-4" /> Add rule</Button
                    >
                </div>
                <div v-if="form.rules.length" class="mt-5 space-y-3">
                    <div
                        v-for="(rule, index) in form.rules"
                        :key="index"
                        class="rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-medium">Rule {{ index + 1 }}</p>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label="Remove rule"
                                @click="removeRule(index)"
                                ><Trash2 class="h-4 w-4"
                            /></Button>
                        </div>
                        <div
                            class="mt-3 grid gap-3 lg:grid-cols-[0.75fr_1.25fr]"
                        >
                            <div>
                                <Input
                                    v-model="rule.title"
                                    :name="`rules.${index}.title`"
                                    placeholder="Rule title"
                                /><InputError
                                    class="mt-2"
                                    :message="
                                        form.errors[`rules.${index}.title`]
                                    "
                                />
                            </div>
                            <div>
                                <Input
                                    v-model="rule.description"
                                    :name="`rules.${index}.description`"
                                    placeholder="Explain the rule"
                                /><InputError
                                    class="mt-2"
                                    :message="
                                        form.errors[
                                            `rules.${index}.description`
                                        ]
                                    "
                                />
                            </div>
                        </div>
                        <label
                            class="mt-3 inline-flex items-center gap-2 text-sm text-muted-foreground"
                            ><input
                                v-model="rule.is_active"
                                type="checkbox"
                                class="h-4 w-4 accent-slate-900"
                            />
                            Show this rule to players</label
                        >
                    </div>
                </div>
                <p
                    v-else
                    class="mt-5 rounded-2xl bg-muted/40 p-4 text-sm text-muted-foreground"
                >
                    No rules added yet.
                </p>
            </section>

            <section
                v-if="mode === 'edit' && turf"
                class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2
                            class="flex items-center gap-2 text-lg font-semibold"
                        >
                            <CircleDot class="h-5 w-5" />Availability
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                turf.status === 'active'
                                    ? 'This turf is active.'
                                    : 'Activate it when the setup is ready.'
                            }}
                        </p>
                    </div>
                    <Button
                        type="button"
                        :variant="
                            turf.status === 'active' ? 'outline' : 'default'
                        "
                        @click="
                            updateStatus(
                                turf.status === 'active'
                                    ? 'inactive'
                                    : 'active',
                            )
                        "
                        >{{
                            turf.status === 'active'
                                ? 'Deactivate turf'
                                : 'Activate turf'
                        }}</Button
                    >
                </div>
            </section>
            <div
                class="sticky bottom-0 flex flex-col-reverse gap-3 rounded-3xl border border-sidebar-border/70 bg-background/95 p-4 backdrop-blur sm:flex-row sm:justify-end dark:border-sidebar-border"
            >
                <Button as-child variant="outline"
                    ><Link :href="routes.index">Cancel</Link></Button
                ><Button type="submit" :disabled="form.processing"
                    ><Save class="h-4 w-4" />{{
                        form.processing
                            ? 'Saving...'
                            : mode === 'create'
                              ? 'Create turf'
                              : 'Save changes'
                    }}</Button
                >
            </div>
        </form>
    </div>
</template>
