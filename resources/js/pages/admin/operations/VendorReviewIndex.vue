<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, ClipboardCheck } from '@lucide/vue';
import EmptyState from '@/components/feedback/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

type VendorReview = {
    id: number;
    display_name: string | null;
    legal_name: string | null;
    submission_version: number;
    submitted_at: string | null;
    review_url: string;
};

defineProps<{
    vendors: VendorReview[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vendor reviews', href: '/admin/operations/vendors' },
        ],
    },
});
</script>

<template>
    <Head title="Vendor reviews" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <section
            class="rounded-3xl border border-sidebar-border/70 bg-sidebar-accent/40 p-5 dark:border-sidebar-border"
        >
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-2">
                    <p
                        class="text-xs font-semibold tracking-[0.24em] text-sidebar-foreground/60 uppercase"
                    >
                        Platform operations
                    </p>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Vendor reviews
                    </h1>
                    <p
                        class="max-w-2xl text-sm leading-6 text-muted-foreground"
                    >
                        Review submitted business evidence before granting
                        vendor operating eligibility.
                    </p>
                </div>
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-background"
                >
                    <ClipboardCheck class="h-5 w-5 text-muted-foreground" />
                </div>
            </div>
        </section>

        <EmptyState
            v-if="vendors.length === 0"
            title="No vendor applications to review"
            description="Submitted vendor applications will appear here when they are ready for a decision."
        />

        <section
            v-else
            class="overflow-hidden rounded-3xl border border-sidebar-border/70 bg-background dark:border-sidebar-border"
        >
            <div
                v-for="vendor in vendors"
                :key="vendor.id"
                class="flex flex-col gap-4 border-b border-sidebar-border/70 p-5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border"
            >
                <div>
                    <h2 class="font-semibold">
                        {{
                            vendor.display_name ??
                            vendor.legal_name ??
                            `Vendor #${vendor.id}`
                        }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ vendor.legal_name ?? 'Legal name pending' }} ·
                        Submission v{{ vendor.submission_version }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Submitted {{ vendor.submitted_at ?? 'recently' }}
                    </p>
                </div>
                <Button as-child variant="outline">
                    <Link :href="vendor.review_url">
                        Review application
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </Button>
            </div>
        </section>
    </div>
</template>
