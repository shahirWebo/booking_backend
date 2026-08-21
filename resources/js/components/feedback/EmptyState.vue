<script setup lang="ts">
import { SearchX } from '@lucide/vue';

withDefaults(
    defineProps<{
        description: string;
        title: string;
    }>(),
    {},
);
</script>

<template>
    <section
        data-slot="empty-state"
        class="app-panel-muted app-stack-sm items-center py-8 text-center"
    >
        <div
            class="flex size-[var(--touch-target-toolbar)] items-center justify-center rounded-full bg-secondary text-secondary-foreground"
        >
            <SearchX class="h-5 w-5" />
        </div>
        <div class="app-stack-sm items-center">
            <h3 class="text-base font-semibold text-slate-950">{{ title }}</h3>
            <p class="app-copy-sm max-w-md">{{ description }}</p>
        </div>
        <div v-if="$slots.default" class="pt-1">
            <slot />
        </div>
    </section>
</template>
