<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { productSurfaces } from '@/lib/productSurfaces';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <section
            class="overflow-hidden rounded-3xl border border-sidebar-border/70 bg-sidebar-accent/40 p-5 dark:border-sidebar-border"
        >
            <div class="flex flex-col gap-3">
                <p
                    class="text-xs font-semibold tracking-[0.24em] text-sidebar-foreground/60 uppercase"
                >
                    Product surfaces
                </p>
                <h1
                    class="text-2xl font-semibold tracking-tight text-sidebar-foreground"
                >
                    Workspace hub
                </h1>
                <p
                    class="max-w-3xl text-sm leading-6 text-sidebar-foreground/70"
                >
                    This authenticated hub points into the three product
                    surfaces established in `WEB-004`, keeping customer, vendor,
                    and admin work separated before we add dedicated mobile
                    navigation in later tasks.
                </p>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <article
                v-for="surface in productSurfaces"
                :key="surface.key"
                class="flex h-full flex-col rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div class="space-y-3">
                    <p
                        class="text-xs font-semibold tracking-[0.24em] text-muted-foreground uppercase"
                    >
                        {{ surface.audience }}
                    </p>
                    <h2 class="text-xl font-semibold tracking-tight">
                        {{ surface.title }}
                    </h2>
                    <p class="text-sm leading-6 text-muted-foreground">
                        {{ surface.summary }}
                    </p>
                </div>

                <div class="mt-5 space-y-2">
                    <p
                        v-for="module in surface.modules"
                        :key="module.key"
                        class="rounded-2xl bg-muted/60 px-3 py-2 text-sm text-muted-foreground"
                    >
                        <span class="font-semibold text-foreground">
                            {{ module.title }}:
                        </span>
                        {{ module.routePrefix }}
                    </p>
                </div>

                <a
                    :href="surface.href"
                    class="mt-5 inline-flex items-center justify-between rounded-2xl border border-sidebar-border/70 px-4 py-3 text-sm font-medium transition hover:bg-muted/60 dark:border-sidebar-border"
                >
                    Open surface map
                    <ArrowRight class="h-4 w-4" />
                </a>
            </article>
        </section>
    </div>
</template>
