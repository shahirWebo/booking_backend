<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
import type { HTMLAttributes } from 'vue';
import MobileSafeArea from '@/components/mobile/MobileSafeArea.vue';
import { cn } from '@/lib/utils';

type MobileBottomNavItem = {
    active?: boolean;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    key: string;
    label: string;
};

const props = defineProps<{
    class?: HTMLAttributes['class'];
    items: MobileBottomNavItem[];
}>();
</script>

<template>
    <MobileSafeArea
        bottom
        left
        right
        data-slot="mobile-bottom-nav"
        :class="
            cn(
                'border-t border-slate-200 bg-white/95 px-4 pt-3 shadow-[0_-18px_50px_-35px_rgba(15,23,42,0.6)] backdrop-blur',
                props.class,
            )
        "
    >
        <div class="mx-auto grid max-w-md grid-cols-4 gap-2 pb-3">
            <Link
                v-for="item in items"
                :key="item.key"
                :href="item.href"
                class="flex min-h-[var(--touch-target-comfortable)] flex-col items-center justify-center gap-1 rounded-2xl px-2 py-2 text-[11px] font-medium transition"
                :class="
                    item.active
                        ? 'bg-slate-950 text-white'
                        : 'text-slate-500 hover:bg-slate-100'
                "
            >
                <component :is="item.icon" v-if="item.icon" class="h-4 w-4" />
                {{ item.label }}
            </Link>
        </div>
    </MobileSafeArea>
</template>
