<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';

const open = defineModel<boolean>('open', { default: false });

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        description?: string;
        title: string;
    }>(),
    {
        description: undefined,
    },
);
</script>

<template>
    <Sheet :open="open" @update:open="open = $event">
        <SheetContent
            side="bottom"
            :class="
                cn(
                    'rounded-t-[var(--radius-surface)] border-t border-slate-200 px-5 pt-3 pb-6',
                    '[&>button]:top-5 [&>button]:right-5',
                    props.class,
                )
            "
        >
            <div
                class="mx-auto mb-3 h-1.5 w-14 rounded-full bg-slate-300"
                aria-hidden="true"
            />

            <SheetHeader class="app-stack-sm text-left">
                <SheetTitle class="app-heading text-left">
                    {{ title }}
                </SheetTitle>
                <SheetDescription v-if="description" class="app-copy-sm">
                    {{ description }}
                </SheetDescription>
            </SheetHeader>

            <div class="mt-4">
                <slot />
            </div>
        </SheetContent>
    </Sheet>
</template>
