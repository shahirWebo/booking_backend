<script setup lang="ts">
import { Spinner } from '@/components/ui/spinner';

withDefaults(
    defineProps<{
        description?: string;
        title?: string;
    }>(),
    {
        description: 'We are preparing this screen for you.',
        title: 'Loading',
    },
);
</script>

<template>
    <section
        data-slot="loading-state"
        class="app-panel-muted app-stack-sm items-center py-8 text-center"
        aria-live="polite"
    >
        <div
            class="flex size-[var(--touch-target-toolbar)] items-center justify-center rounded-full bg-secondary text-secondary-foreground"
        >
            <Spinner class="h-5 w-5" />
        </div>
        <div class="app-stack-sm items-center">
            <h3 class="text-base font-semibold text-slate-950">{{ title }}</h3>
            <p class="app-copy-sm max-w-md">{{ description }}</p>
        </div>
    </section>
</template>
