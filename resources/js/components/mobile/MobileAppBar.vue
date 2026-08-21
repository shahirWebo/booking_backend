<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { ChevronLeft } from '@lucide/vue';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
    eyebrow?: string;
    leadingHref?: NonNullable<InertiaLinkProps['href']>;
    leadingLabel?: string;
    subtitle?: string;
    title: string;
}>();
</script>

<template>
    <header data-slot="mobile-app-bar" :class="cn('app-stack-sm', props.class)">
        <div class="flex items-start justify-between gap-3">
            <div class="app-stack-sm min-w-0 flex-1">
                <p v-if="eyebrow" class="app-eyebrow">{{ eyebrow }}</p>
                <div class="app-stack-sm">
                    <h1 class="app-heading">{{ title }}</h1>
                    <p v-if="subtitle" class="app-copy-sm">{{ subtitle }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>

        <Link
            v-if="leadingHref"
            :href="leadingHref"
            class="app-chip w-fit border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
        >
            <ChevronLeft class="h-4 w-4" />
            {{ leadingLabel ?? 'Back' }}
        </Link>
    </header>
</template>
