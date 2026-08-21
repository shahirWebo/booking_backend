<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="app-screen flex min-h-svh flex-col items-center justify-center bg-background"
    >
        <div class="app-shell w-full max-w-md px-6 sm:px-0">
            <div class="app-panel app-stack gap-8">
                <div class="app-stack-sm items-center text-center">
                    <Link
                        :href="home()"
                        class="app-stack-sm items-center font-medium"
                    >
                        <div
                            class="flex size-[var(--touch-target-toolbar)] items-center justify-center rounded-[var(--radius-surface)] bg-secondary text-secondary-foreground"
                        >
                            <AppLogoIcon
                                class="size-9 fill-current text-[var(--foreground)] dark:text-white"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="app-stack-sm text-center">
                        <h1 class="app-heading">{{ title }}</h1>
                        <p class="app-copy-sm text-center">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
