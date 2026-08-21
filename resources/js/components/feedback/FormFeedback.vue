<script setup lang="ts">
import { AlertCircle, CheckCircle2, Info, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        description?: string;
        message: string;
        variant?: 'error' | 'info' | 'success' | 'warning';
    }>(),
    {
        description: undefined,
        variant: 'info',
    },
);

const styles = computed(() => {
    switch (props.variant) {
        case 'success':
            return {
                className: 'border-emerald-200 bg-emerald-50 text-emerald-900',
                icon: CheckCircle2,
            };
        case 'warning':
            return {
                className: 'border-amber-200 bg-amber-50 text-amber-900',
                icon: TriangleAlert,
            };
        case 'error':
            return {
                className: 'border-red-200 bg-red-50 text-red-800',
                icon: AlertCircle,
            };
        default:
            return {
                className: 'border-sky-200 bg-sky-50 text-sky-900',
                icon: Info,
            };
    }
});
</script>

<template>
    <div
        data-slot="form-feedback"
        :class="
            cn(
                'rounded-[var(--radius-control)] border px-4 py-3 shadow-sm',
                styles.className,
            )
        "
    >
        <div class="flex items-start gap-3">
            <component :is="styles.icon" class="mt-0.5 h-4 w-4 shrink-0" />

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold">{{ message }}</p>
                <p v-if="description" class="mt-1 text-sm/6 opacity-90">
                    {{ description }}
                </p>
                <div v-if="$slots.default" class="mt-2">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
