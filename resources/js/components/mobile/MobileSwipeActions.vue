<script setup lang="ts">
import { MoreHorizontal } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type SwipeAction = {
    destructive?: boolean;
    key: string;
    label: string;
};

const props = withDefaults(
    defineProps<{
        actions: SwipeAction[];
        class?: string;
    }>(),
    {
        class: undefined,
    },
);

const emit = defineEmits<{
    (e: 'action', key: string): void;
}>();

const revealActions = ref(false);
const touchStartX = ref<number | null>(null);

const contentClass = computed(() =>
    revealActions.value ? '-translate-x-[7.5rem]' : 'translate-x-0',
);

function onTouchStart(event: TouchEvent): void {
    touchStartX.value = event.changedTouches[0]?.clientX ?? null;
}

function onTouchEnd(event: TouchEvent): void {
    const startX = touchStartX.value;
    const endX = event.changedTouches[0]?.clientX ?? startX;

    if (startX === null || endX === undefined) {
        return;
    }

    const delta = endX - startX;

    if (delta < -36) {
        revealActions.value = true;
    }

    if (delta > 36) {
        revealActions.value = false;
    }
}

function handleAction(key: string): void {
    revealActions.value = false;
    emit('action', key);
}
</script>

<template>
    <div
        data-slot="mobile-swipe-actions"
        :class="
            cn(
                'relative overflow-hidden rounded-[var(--radius-surface)] border border-slate-200 bg-white',
                props.class,
            )
        "
    >
        <div class="absolute inset-y-0 right-0 flex">
            <button
                v-for="action in actions"
                :key="action.key"
                type="button"
                class="flex min-w-[3.75rem] items-center justify-center px-3 text-xs font-semibold tracking-[0.14em] uppercase"
                :class="
                    action.destructive
                        ? 'bg-destructive text-white'
                        : 'bg-slate-900 text-white'
                "
                @click="handleAction(action.key)"
            >
                {{ action.label }}
            </button>
        </div>

        <div
            class="relative transition-transform duration-200 ease-out"
            :class="contentClass"
            @touchstart.passive="onTouchStart"
            @touchend.passive="onTouchEnd"
        >
            <div class="app-interactive-card bg-white">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <slot />
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        class="shrink-0"
                        @click="revealActions = !revealActions"
                    >
                        <MoreHorizontal class="h-4 w-4" />
                        <span class="sr-only">Toggle actions</span>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
