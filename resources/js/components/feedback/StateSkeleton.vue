<script setup lang="ts">
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';

withDefaults(
    defineProps<{
        lines?: number;
    }>(),
    {
        lines: 3,
    },
);
</script>

<template>
    <section data-slot="state-skeleton" class="app-panel-muted app-stack-sm">
        <Skeleton class="h-5 w-28 rounded-full" />
        <Skeleton class="h-8 w-2/3" />
        <div class="app-stack-sm">
            <Skeleton
                v-for="index in lines"
                :key="index"
                class="h-4 w-full last:w-5/6"
            />
        </div>
    </section>
</template>
