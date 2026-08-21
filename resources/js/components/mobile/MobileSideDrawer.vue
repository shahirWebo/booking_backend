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
        side?: 'left' | 'right';
        title: string;
    }>(),
    {
        description: undefined,
        side: 'left',
    },
);
</script>

<template>
    <Sheet :open="open" @update:open="open = $event">
        <SheetContent
            :side="side"
            :class="
                cn(
                    'w-[min(88vw,22rem)] border-slate-200 px-5 pt-5 pb-6',
                    props.class,
                )
            "
        >
            <SheetHeader class="app-stack-sm text-left">
                <SheetTitle class="app-heading text-left">
                    {{ title }}
                </SheetTitle>
                <SheetDescription v-if="description" class="app-copy-sm">
                    {{ description }}
                </SheetDescription>
            </SheetHeader>

            <div class="mt-5 h-full">
                <slot />
            </div>
        </SheetContent>
    </Sheet>
</template>
