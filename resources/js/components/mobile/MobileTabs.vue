<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

type MobileTabItem = {
    key: string;
    label: string;
};

const selected = defineModel<string>('modelValue', { required: true });

const props = defineProps<{
    class?: HTMLAttributes['class'];
    items: MobileTabItem[];
}>();
</script>

<template>
    <div
        data-slot="mobile-tabs"
        :class="
            cn(
                'inline-grid min-h-[var(--touch-target-comfortable)] grid-cols-2 rounded-[var(--radius-chip)] bg-secondary p-1',
                props.class,
            )
        "
    >
        <button
            v-for="item in items"
            :key="item.key"
            type="button"
            class="app-chip justify-center rounded-[var(--radius-chip)] px-4 text-center transition"
            :class="
                selected === item.key
                    ? 'bg-white text-slate-950 shadow-sm'
                    : 'text-slate-600'
            "
            @click="selected = item.key"
        >
            {{ item.label }}
        </button>
    </div>
</template>
