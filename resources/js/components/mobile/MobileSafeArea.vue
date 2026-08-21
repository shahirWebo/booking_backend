<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        bottom?: boolean;
        left?: boolean;
        right?: boolean;
        top?: boolean;
    }>(),
    {
        bottom: false,
        left: false,
        right: false,
        top: false,
    },
);

const safeAreaStyle = computed(() => ({
    paddingBottom: props.bottom ? 'env(safe-area-inset-bottom)' : undefined,
    paddingLeft: props.left ? 'env(safe-area-inset-left)' : undefined,
    paddingRight: props.right ? 'env(safe-area-inset-right)' : undefined,
    paddingTop: props.top ? 'env(safe-area-inset-top)' : undefined,
}));
</script>

<template>
    <div
        data-slot="mobile-safe-area"
        :class="cn(props.class)"
        :style="safeAreaStyle"
    >
        <slot />
    </div>
</template>
