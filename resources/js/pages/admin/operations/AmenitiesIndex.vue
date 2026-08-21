<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, ListTree, Plus, Trash2 } from '@lucide/vue';
import EmptyState from '@/components/feedback/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';

type AmenityItem = {
    id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
    created_at: string | null;
    updated_at: string | null;
    routes: {
        edit: string;
        destroy: string;
    };
};

const props = defineProps<{
    amenities: AmenityItem[];
    routes: {
        create: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Amenities', href: admin.amenities.index() },
        ],
    },
});

function destroyAmenity(amenity: AmenityItem): void {
    if (!window.confirm(`Delete ${amenity.name}? This cannot be undone.`)) {
        return;
    }

    router.delete(amenity.routes.destroy, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Amenities" />

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
                        Admin catalog
                    </p>
                    <h1
                        class="text-2xl font-semibold tracking-tight text-sidebar-foreground"
                    >
                        Amenities
                    </h1>
                    <p
                        class="max-w-3xl text-sm leading-6 text-sidebar-foreground/70"
                    >
                        Review the shared amenities catalog and open dedicated
                        pages to add or update each entry.
                    </p>
                </div>

                <Button as-child>
                    <Link :href="props.routes.create">
                        <Plus class="h-4 w-4" />
                        Add amenity
                    </Link>
                </Button>
            </div>
        </section>

        <section v-if="amenities.length" class="grid gap-4 xl:grid-cols-2">
            <article
                v-for="amenity in amenities"
                :key="amenity.id"
                class="flex h-full flex-col rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-semibold tracking-tight">
                                {{ amenity.name }}
                            </h2>
                            <span
                                class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase"
                                :class="
                                    amenity.is_active
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : 'bg-slate-200 text-slate-700'
                                "
                            >
                                {{ amenity.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p
                            class="text-xs font-semibold tracking-[0.24em] text-muted-foreground uppercase"
                        >
                            {{ amenity.code }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-muted/70"
                    >
                        <ListTree class="h-5 w-5 text-muted-foreground" />
                    </div>
                </div>

                <p class="mt-4 text-sm leading-6 text-muted-foreground">
                    {{
                        amenity.description ??
                        'No description has been added for this amenity yet.'
                    }}
                </p>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <Button as-child class="sm:flex-1">
                        <Link :href="amenity.routes.edit">
                            Edit amenity
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        class="border-red-200 text-red-700 hover:bg-red-50 sm:flex-1"
                        @click="destroyAmenity(amenity)"
                    >
                        <Trash2 class="h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </article>
        </section>

        <EmptyState
            v-else
            title="No amenities yet"
            description="Add the first amenity to populate the shared catalog."
        >
            <Button as-child>
                <Link :href="props.routes.create">Add amenity</Link>
            </Button>
        </EmptyState>
    </div>
</template>
