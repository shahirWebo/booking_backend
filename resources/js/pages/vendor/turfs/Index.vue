<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    CircleDot,
    ImagePlus,
    Plus,
    Ruler,
    Sparkles,
} from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/feedback/EmptyState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Turf = {
    id: number;
    name: string;
    status: 'active' | 'inactive';
    surface_type: string | null;
    is_indoor: boolean;
    capacity_count: number | null;
    sport_ids: number[];
    images: Array<{ id: number }>;
    routes: { edit: string };
};
const props = defineProps<{
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
    turfs: Turf[];
    routes: { create: string; location_edit: string; locations_index: string };
}>();
const summary = computed(() => ({
    active: props.turfs.filter((turf) => turf.status === 'active').length,
    images: props.turfs.reduce((total, turf) => total + turf.images.length, 0),
}));
</script>

<template>
    <Head :title="`${location.name} Turfs`" />
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <section
            class="overflow-hidden rounded-3xl border border-sidebar-border/70 bg-sidebar-accent/40 p-5 dark:border-sidebar-border"
        >
            <div
                class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"
            >
                <div>
                    <Link
                        :href="routes.location_edit"
                        class="inline-flex items-center gap-2 text-sm font-medium text-sidebar-foreground/70 hover:text-sidebar-foreground"
                        ><ArrowLeft class="h-4 w-4" /> Back to location</Link
                    >
                    <p
                        class="mt-5 text-xs font-semibold tracking-[0.24em] text-sidebar-foreground/60 uppercase"
                    >
                        {{ location.city }}, {{ location.state }}
                    </p>
                    <h1
                        class="mt-2 text-2xl font-semibold tracking-tight text-sidebar-foreground"
                    >
                        {{ location.name }} turfs
                    </h1>
                    <p
                        class="mt-2 max-w-2xl text-sm leading-6 text-sidebar-foreground/70"
                    >
                        Manage the playable surfaces available at this venue.
                    </p>
                </div>
                <Button as-child
                    ><Link :href="routes.create"
                        ><Plus class="h-4 w-4" /> Add turf</Link
                    ></Button
                >
            </div>
            <div class="mt-5 grid max-w-lg grid-cols-3 gap-3">
                <div class="rounded-2xl bg-background px-4 py-3">
                    <p
                        class="text-[11px] font-semibold text-muted-foreground uppercase"
                    >
                        Total
                    </p>
                    <p class="mt-1 text-xl font-semibold">{{ turfs.length }}</p>
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
                        Images
                    </p>
                    <p class="mt-1 text-xl font-semibold">
                        {{ summary.images }}
                    </p>
                </div>
            </div>
        </section>
        <section v-if="turfs.length" class="grid gap-4 xl:grid-cols-2">
            <article
                v-for="turf in turfs"
                :key="turf.id"
                class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-semibold tracking-tight">
                                {{ turf.name }}
                            </h2>
                            <span
                                class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase"
                                :class="
                                    turf.status === 'active'
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : 'bg-slate-200 text-slate-700'
                                "
                                >{{ turf.status }}</span
                            >
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ turf.surface_type || 'Surface type pending' }} ·
                            {{ turf.is_indoor ? 'Indoor' : 'Outdoor' }}
                        </p>
                    </div>
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-muted/70"
                    >
                        <Ruler class="h-5 w-5 text-muted-foreground" />
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <Badge variant="secondary" class="rounded-full"
                        ><Sparkles class="mr-1 h-3.5 w-3.5" />{{
                            turf.sport_ids.length
                        }}
                        sports</Badge
                    ><Badge variant="secondary" class="rounded-full"
                        ><ImagePlus class="mr-1 h-3.5 w-3.5" />{{
                            turf.images.length
                        }}
                        images</Badge
                    ><Badge variant="secondary" class="rounded-full"
                        ><CircleDot class="mr-1 h-3.5 w-3.5" />{{
                            turf.capacity_count
                                ? `${turf.capacity_count} players`
                                : 'Capacity pending'
                        }}</Badge
                    >
                </div>
                <div class="mt-5">
                    <Button as-child class="w-full"
                        ><Link :href="turf.routes.edit"
                            >Edit turf <ArrowRight class="h-4 w-4" /></Link
                    ></Button>
                </div>
            </article>
        </section>
        <EmptyState
            v-else
            title="No turfs yet"
            description="Create the first playable surface for this location, then add its sports, amenities, gallery, and rules."
            ><Button as-child
                ><Link :href="routes.create">Add turf</Link></Button
            ></EmptyState
        >
    </div>
</template>
