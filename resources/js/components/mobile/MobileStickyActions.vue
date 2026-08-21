<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import MobileSafeArea from '@/components/mobile/MobileSafeArea.vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <MobileSafeArea
        bottom
        left
        right
        data-slot="mobile-sticky-actions"
        :class="
            cn(
                'sticky bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 pt-3 shadow-[0_-18px_40px_-32px_rgba(15,23,42,0.35)] backdrop-blur',
                props.class,
            )
        "
    >
        <div class="mx-auto flex max-w-3xl flex-col gap-3 pb-3 sm:flex-row">
            <slot />
        </div>
    </MobileSafeArea>
</template>
