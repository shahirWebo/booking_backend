<script setup lang="ts">
import FormFeedback from '@/components/feedback/FormFeedback.vue';
import { Button } from '@/components/ui/button';

const props = withDefaults(
    defineProps<{
        description?: string;
        retryLabel?: string;
        title: string;
    }>(),
    {
        description: undefined,
        retryLabel: undefined,
    },
);

const emit = defineEmits<{
    (e: 'retry'): void;
}>();
</script>

<template>
    <section data-slot="error-state" class="app-stack-sm">
        <FormFeedback
            :message="title"
            :description="description"
            variant="error"
        >
            <Button
                v-if="props.retryLabel"
                type="button"
                variant="link"
                class="h-auto p-0 text-red-700"
                @click="emit('retry')"
            >
                {{ retryLabel }}
            </Button>
        </FormFeedback>
    </section>
</template>
