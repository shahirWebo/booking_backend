<script setup lang="ts">
import { CalendarRange, CreditCard, MapPinned, Settings2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import MobileAppBar from '@/components/mobile/MobileAppBar.vue';
import MobileBottomSheet from '@/components/mobile/MobileBottomSheet.vue';
import MobileFullScreenModal from '@/components/mobile/MobileFullScreenModal.vue';
import MobileSideDrawer from '@/components/mobile/MobileSideDrawer.vue';
import MobileStickyActions from '@/components/mobile/MobileStickyActions.vue';
import MobileSwipeActions from '@/components/mobile/MobileSwipeActions.vue';
import MobileTabs from '@/components/mobile/MobileTabs.vue';
import { Button } from '@/components/ui/button';

const previewTab = ref('tabs');
const drawerOpen = ref(false);
const sheetOpen = ref(false);
const modalOpen = ref(false);
const swipeMessage = ref('No action selected yet.');

const previewTabs: { key: string; label: string }[] = [
    { key: 'tabs', label: 'Tabs' },
    { key: 'swipe', label: 'Swipe' },
];

const bottomSheetFeatures = computed(() => [
    'Bottom sheets anchor actions close to the thumb zone.',
    'Drawers support one-handed navigation from either edge.',
    'Full-screen modals keep multi-step mobile flows focused.',
]);

function handleSwipeAction(key: string): void {
    swipeMessage.value = `Selected "${key}" action from the swipe menu preview.`;
}
</script>

<template>
    <section class="app-panel overflow-hidden">
        <div class="app-stack">
            <MobileAppBar
                eyebrow="TW-004 foundation"
                title="Shared mobile primitives"
                subtitle="App bars, drawers, sheets, tabs, sticky actions, swipe menus, and safe-area-aware bottom navigation are now reusable Vue building blocks."
            >
                <template #actions>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="drawerOpen = true"
                    >
                        Menu
                    </Button>
                </template>
            </MobileAppBar>

            <MobileTabs
                v-model="previewTab"
                :items="previewTabs"
                class="w-full sm:w-fit"
            />

            <div v-if="previewTab === 'tabs'" class="grid gap-3 lg:grid-cols-3">
                <button
                    type="button"
                    class="app-panel-muted app-interactive-card text-left"
                    @click="sheetOpen = true"
                >
                    <MapPinned class="mb-3 h-5 w-5 text-primary" />
                    <p class="text-base font-semibold text-slate-950">
                        Bottom sheet
                    </p>
                    <p class="app-copy-sm mt-1">
                        Use for action pickers, confirmations, and short task
                        flows.
                    </p>
                </button>

                <button
                    type="button"
                    class="app-panel-muted app-interactive-card text-left"
                    @click="drawerOpen = true"
                >
                    <Settings2 class="mb-3 h-5 w-5 text-primary" />
                    <p class="text-base font-semibold text-slate-950">
                        Side drawer
                    </p>
                    <p class="app-copy-sm mt-1">
                        Use for navigation stacks, filters, and secondary
                        controls.
                    </p>
                </button>

                <button
                    type="button"
                    class="app-panel-muted app-interactive-card text-left"
                    @click="modalOpen = true"
                >
                    <CreditCard class="mb-3 h-5 w-5 text-primary" />
                    <p class="text-base font-semibold text-slate-950">
                        Full-screen modal
                    </p>
                    <p class="app-copy-sm mt-1">
                        Use when the user should stay in one dedicated mobile
                        flow.
                    </p>
                </button>
            </div>

            <div v-else class="app-stack-sm">
                <MobileSwipeActions
                    :actions="[
                        { key: 'reschedule', label: 'Edit' },
                        { key: 'cancel', label: 'Cancel', destructive: true },
                    ]"
                    @action="handleSwipeAction"
                >
                    <p class="text-base font-semibold text-slate-950">
                        Booking hold for 7:30 PM
                    </p>
                    <p class="app-copy-sm mt-1">
                        Swipe left or tap the overflow control to reveal row
                        actions.
                    </p>
                </MobileSwipeActions>

                <p class="app-copy-sm">{{ swipeMessage }}</p>
            </div>
        </div>

        <MobileStickyActions class="mt-6">
            <Button type="button" variant="outline" class="w-full sm:flex-1">
                Preview secondary action
            </Button>
            <Button type="button" class="w-full sm:flex-1">
                Preview primary action
            </Button>
        </MobileStickyActions>

        <MobileBottomSheet
            v-model:open="sheetOpen"
            title="Mobile bottom sheet"
            description="Shared short-form surface for quick selection and confirmation flows."
        >
            <div class="app-stack-sm">
                <p
                    v-for="feature in bottomSheetFeatures"
                    :key="feature"
                    class="app-panel-muted app-copy-sm"
                >
                    {{ feature }}
                </p>
            </div>
        </MobileBottomSheet>

        <MobileSideDrawer
            v-model:open="drawerOpen"
            title="Mobile side drawer"
            description="Reusable navigation and filter container for off-canvas mobile content."
        >
            <div class="app-stack-sm">
                <Button variant="ghost" class="justify-start">
                    <CalendarRange class="h-4 w-4" />
                    Today's operations
                </Button>
                <Button variant="ghost" class="justify-start">
                    <MapPinned class="h-4 w-4" />
                    Location filters
                </Button>
                <Button variant="ghost" class="justify-start">
                    <Settings2 class="h-4 w-4" />
                    Workspace preferences
                </Button>
            </div>
        </MobileSideDrawer>

        <MobileFullScreenModal
            v-model:open="modalOpen"
            title="Full-screen mobile flow"
            description="Use this pattern for immersive multi-step journeys that should not feel like a desktop dialog."
        >
            <div class="app-stack">
                <div class="app-panel-muted">
                    <p class="text-base font-semibold text-slate-950">
                        Modal body
                    </p>
                    <p class="app-copy-sm mt-1">
                        The full-screen modal preserves a native-like vertical
                        flow on phones while still collapsing back to a centered
                        dialog on larger screens.
                    </p>
                </div>

                <Button type="button" class="w-full" @click="modalOpen = false">
                    Close preview
                </Button>
            </div>
        </MobileFullScreenModal>
    </section>
</template>
