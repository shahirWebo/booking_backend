<script setup lang="ts">
import EmptyState from '@/components/feedback/EmptyState.vue';
import ErrorState from '@/components/feedback/ErrorState.vue';
import FormFeedback from '@/components/feedback/FormFeedback.vue';
import LoadingState from '@/components/feedback/LoadingState.vue';
import StateSkeleton from '@/components/feedback/StateSkeleton.vue';
import { Button } from '@/components/ui/button';
</script>

<template>
    <section class="app-panel">
        <div class="app-stack">
            <div class="app-stack-sm">
                <p class="app-eyebrow">TW-005 foundation</p>
                <h2 class="app-heading">Reusable feedback states</h2>
                <p class="app-copy">
                    Loading, empty, error, skeleton, and form-feedback patterns
                    now share one visual language instead of screen-specific
                    alert boxes.
                </p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <LoadingState
                    title="Loading bookings"
                    description="The app can use this state while nearby availability is syncing."
                />

                <StateSkeleton :lines="4" />

                <EmptyState
                    title="No saved venues yet"
                    description="Use the empty state when a request succeeds but there is nothing to display."
                >
                    <Button type="button" variant="outline" size="sm">
                        Explore venues
                    </Button>
                </EmptyState>

                <ErrorState
                    title="Could not load vendor analytics"
                    description="Use the shared error state when retrying the request is safe and meaningful."
                    retry-label="Retry request"
                />
            </div>

            <FormFeedback
                message="Shared form feedback"
                description="The login and registration flows now reuse this surface for submission-level guidance and retry affordances."
                variant="info"
            />
        </div>
    </section>
</template>
