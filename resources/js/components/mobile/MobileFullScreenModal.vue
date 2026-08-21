<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent
            :show-close-button="true"
            :class="
                cn(
                    'top-0 left-0 h-svh max-w-none translate-x-0 translate-y-0 rounded-none border-0 p-0 sm:top-[50%] sm:left-[50%] sm:h-auto sm:max-w-2xl sm:translate-x-[-50%] sm:translate-y-[-50%] sm:rounded-[var(--radius-surface)] sm:border sm:p-0',
                    props.class,
                )
            "
        >
            <div class="flex h-full flex-col bg-background">
                <DialogHeader
                    class="border-b border-slate-200 px-5 pt-5 pb-4 text-left"
                >
                    <DialogTitle class="app-heading text-left">
                        {{ title }}
                    </DialogTitle>
                    <DialogDescription v-if="description" class="app-copy-sm">
                        {{ description }}
                    </DialogDescription>
                </DialogHeader>

                <div class="flex-1 overflow-y-auto px-5 py-5">
                    <slot />
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
