<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Plus, Trash2, Trophy } from '@lucide/vue';
import EmptyState from '@/components/feedback/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';

type SportItem = {
    id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
    icon_asset_key: string | null;
    icon_alt_text: string | null;
    image_asset_key: string | null;
    image_alt_text: string | null;
    created_at: string | null;
    updated_at: string | null;
    routes: {
        edit: string;
        destroy: string;
    };
};

const props = defineProps<{
    sports: SportItem[];
    routes: {
        create: string;
        publicIndex: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Sports', href: admin.sports.index() },
        ],
    },
});

function destroySport(sport: SportItem): void {
    if (!window.confirm(`Delete ${sport.name}? This cannot be undone.`)) {
        return;
    }

    router.delete(sport.routes.destroy, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Sports" />

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
                        Sports
                    </h1>
                    <p
                        class="max-w-3xl text-sm leading-6 text-sidebar-foreground/70"
                    >
                        Review the shared sports catalog and open dedicated
                        pages to add or update each entry.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <a
                        :href="props.routes.publicIndex"
                        class="inline-flex items-center justify-center rounded-2xl border border-sidebar-border/70 px-4 py-3 text-sm font-medium transition hover:bg-muted/60 dark:border-sidebar-border"
                    >
                        Public API
                    </a>
                    <Button as-child>
                        <Link :href="props.routes.create">
                            <Plus class="h-4 w-4" />
                            Add sport
                        </Link>
                    </Button>
                </div>
            </div>
        </section>

        <section v-if="sports.length" class="grid gap-4 xl:grid-cols-2">
            <article
                v-for="sport in sports"
                :key="sport.id"
                class="flex h-full flex-col rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-semibold tracking-tight">
                                {{ sport.name }}
                            </h2>
                            <span
                                class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase"
                                :class="
                                    sport.is_active
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : 'bg-slate-200 text-slate-700'
                                "
                            >
                                {{ sport.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p
                            class="text-xs font-semibold tracking-[0.24em] text-muted-foreground uppercase"
                        >
                            {{ sport.code }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-muted/70"
                    >
                        <Trophy class="h-5 w-5 text-muted-foreground" />
                    </div>
                </div>

                <p class="mt-4 text-sm leading-6 text-muted-foreground">
                    {{
                        sport.description ??
                        'No description has been added for this sport yet.'
                    }}
                </p>

                <dl class="mt-4 grid gap-3 text-sm text-muted-foreground">
                    <div class="rounded-2xl bg-muted/60 px-3 py-3">
                        <dt class="font-semibold text-foreground">
                            Icon asset
                        </dt>
                        <dd class="mt-1 break-all">
                            {{ sport.icon_asset_key ?? 'Not provided' }}
                        </dd>
                    </div>
                    <div class="rounded-2xl bg-muted/60 px-3 py-3">
                        <dt class="font-semibold text-foreground">
                            Image asset
                        </dt>
                        <dd class="mt-1 break-all">
                            {{ sport.image_asset_key ?? 'Not provided' }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <Button as-child class="sm:flex-1">
                        <Link :href="sport.routes.edit">
                            Edit sport
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        class="border-red-200 text-red-700 hover:bg-red-50 sm:flex-1"
                        @click="destroySport(sport)"
                    >
                        <Trash2 class="h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </article>
        </section>

        <EmptyState
            v-else
            title="No sports yet"
            description="Add the first sport to populate the shared catalog."
        >
            <Button as-child>
                <Link :href="props.routes.create">Add sport</Link>
            </Button>
        </EmptyState>
    </div>
</template>
