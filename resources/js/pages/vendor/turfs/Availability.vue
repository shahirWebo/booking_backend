<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Ban,
    CalendarDays,
    Clock3,
    Copy,
    Save,
    Trash2,
    Wrench,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Range = {
    starts_at_time: string;
    ends_at_time: string;
    ends_next_day: boolean;
};
type Rule = { weekday: number; is_active: boolean; time_ranges: Range[] };
type SlotBlock = {
    id: number;
    block_date: string;
    is_full_day: boolean;
    starts_at_time: string | null;
    ends_at_time: string | null;
    reason: string | null;
    delete_url: string;
};
type MaintenanceBlock = {
    id: number;
    starts_at_local: string;
    ends_at_local: string;
    reason: string | null;
    delete_url: string;
};
const props = defineProps<{
    turf: {
        name: string;
        location_name: string;
        timezone: string;
        booking_lead_time_minutes: number;
        advance_booking_window_days: number;
        default_slot_duration_minutes: number;
        availability_schedule: Rule[];
        slot_blocks: SlotBlock[];
        maintenance_blocks: MaintenanceBlock[];
    };
    copy_targets: Array<{ id: number; name: string }>;
    routes: {
        back: string;
        schedule: string;
        configuration: string;
        slots: string;
        slot_blocks: string;
        maintenance_blocks: string;
        copy_schedule: string;
        pricing?: string;
    };
}>();
const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const schedule = useForm({
    availability_rules: labels.map(
        (_, index) =>
            props.turf.availability_schedule.find(
                (rule) => rule.weekday === index + 1,
            ) ?? {
                weekday: index + 1,
                is_active: false,
                time_ranges: [] as Range[],
            },
    ),
});
const configuration = useForm({
    default_slot_duration_minutes: props.turf.default_slot_duration_minutes,
    booking_lead_time_minutes: props.turf.booking_lead_time_minutes,
    advance_booking_window_days: props.turf.advance_booking_window_days,
});
const date = ref(new Date().toISOString().slice(0, 10));
const slots = ref<
    Array<{ starts_at: string; starts_at_time: string; ends_at_time: string }>
>([]);
const loadingSlots = ref(false);
const slotError = ref('');
const hasPreviewed = ref(false);
const blockForm = useForm({
    block_date: date.value,
    is_full_day: true,
    starts_at_time: '09:00',
    ends_at_time: '10:00',
    ends_next_day: false,
    reason: '',
});
const maintenanceForm = useForm({
    starts_at_local: `${date.value}T09:00`,
    ends_at_local: `${date.value}T10:00`,
    reason: '',
});
const copyForm = useForm({
    target_turf_id: props.copy_targets[0]?.id ?? (null as number | null),
});
const activeDays = computed(
    () => schedule.availability_rules.filter((rule) => rule.is_active).length,
);
function addRange(rule: Rule): void {
    rule.time_ranges.push({
        starts_at_time: '09:00:00',
        ends_at_time: '10:00:00',
        ends_next_day: false,
    });
}
function copyDay(source: Rule): void {
    schedule.availability_rules.forEach((rule) => {
        if (rule.weekday !== source.weekday) {
            rule.is_active = source.is_active;
            rule.time_ranges = source.time_ranges.map((range) => ({
                ...range,
            }));
        }
    });
}
function submitBlock(): void {
    blockForm.post(props.routes.slot_blocks, {
        preserveScroll: true,
        onSuccess: () => blockForm.reset('reason'),
    });
}
function submitMaintenance(): void {
    maintenanceForm.post(props.routes.maintenance_blocks, {
        preserveScroll: true,
        onSuccess: () => maintenanceForm.reset('reason'),
    });
}
function deleteItem(url: string): void {
    router.delete(url, { preserveScroll: true });
}
function copyToTurf(): void {
    if (copyForm.target_turf_id) {
        copyForm.post(props.routes.copy_schedule, { preserveScroll: true });
    }
}
async function loadSlots(): Promise<void> {
    loadingSlots.value = true;
    slotError.value = '';
    hasPreviewed.value = true;

    try {
        const response = await fetch(
            `${props.routes.slots}?date=${date.value}`,
            { headers: { Accept: 'application/json' } },
        );

        if (!response.ok) {
            throw new Error('Unable to load availability for this date.');
        }

        const payload = (await response.json()) as {
            slots?: Array<{
                starts_at: string;
                starts_at_time: string;
                ends_at_time: string;
            }>;
        };
        slots.value = payload.slots ?? [];
    } catch (error) {
        slots.value = [];
        slotError.value =
            error instanceof Error
                ? error.message
                : 'Unable to load availability.';
    } finally {
        loadingSlots.value = false;
    }
}
</script>

<template>
    <Head :title="`Availability · ${turf.name}`" />
    <main class="mx-auto flex w-full max-w-5xl flex-col gap-4 p-4 pb-10">
        <section class="rounded-3xl bg-slate-950 p-5 text-white">
            <Link
                :href="routes.back"
                class="inline-flex items-center gap-2 text-sm text-slate-300"
                ><ArrowLeft class="h-4 w-4" /> Turf details</Link
            >
            <Link
                v-if="routes.pricing"
                :href="routes.pricing"
                class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-amber-300"
                ><Clock3 class="h-4 w-4" /> Manage pricing</Link
            >
            <p
                class="mt-6 text-xs font-semibold tracking-[.2em] text-emerald-300 uppercase"
            >
                {{ turf.location_name }} · {{ turf.timezone }}
            </p>
            <h1 class="mt-2 text-3xl font-semibold">Booking rhythm</h1>
            <p class="mt-2 text-sm text-slate-300">
                Shape the weekly window, then inspect the slots customers can
                actually see.
            </p>
        </section>
        <section class="rounded-3xl border bg-background p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">Weekly schedule</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ activeDays }} active days. Local times only.
                    </p>
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <select
                        v-if="copy_targets.length"
                        v-model="copyForm.target_turf_id"
                        aria-label="Copy schedule target"
                        class="h-9 rounded-md border bg-background px-3 text-sm text-foreground"
                    >
                        <option
                            v-for="target in copy_targets"
                            :key="target.id"
                            :value="target.id"
                        >
                            {{ target.name }}
                        </option>
                    </select>
                    <Button
                        v-if="copy_targets.length"
                        type="button"
                        variant="outline"
                        :disabled="copyForm.processing"
                        @click="copyToTurf"
                        ><Copy class="h-4 w-4" /> Copy to turf</Button
                    >
                    <Button
                        :disabled="schedule.processing"
                        @click="schedule.put(routes.schedule)"
                        ><Save class="h-4 w-4" /> Save</Button
                    >
                </div>
            </div>
            <div class="mt-5 space-y-3">
                <article
                    v-for="(rule, index) in schedule.availability_rules"
                    :key="rule.weekday"
                    class="rounded-2xl border p-4"
                    :class="
                        rule.is_active
                            ? 'border-emerald-200 bg-emerald-50/40'
                            : ''
                    "
                >
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-3 font-semibold"
                            ><input
                                v-model="rule.is_active"
                                type="checkbox"
                                class="h-5 w-5 accent-emerald-700"
                                @change="
                                    rule.is_active &&
                                    rule.time_ranges.length === 0 &&
                                    addRange(rule)
                                "
                            />{{ labels[index] }}</label
                        ><Button
                            size="sm"
                            type="button"
                            variant="ghost"
                            @click="copyDay(rule)"
                            ><Copy class="h-4 w-4" /> Copy</Button
                        >
                    </div>
                    <div v-if="rule.is_active" class="mt-4 space-y-2">
                        <div
                            v-for="(range, rangeIndex) in rule.time_ranges"
                            :key="rangeIndex"
                            class="grid grid-cols-[1fr_1fr_auto] gap-2"
                        >
                            <Input
                                v-model="range.starts_at_time"
                                type="time"
                            /><Input
                                v-model="range.ends_at_time"
                                type="time"
                            /><Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="rule.time_ranges.splice(rangeIndex, 1)"
                                >Remove</Button
                            >
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="addRange(rule)"
                            >Add hours</Button
                        >
                    </div>
                </article>
            </div>
        </section>
        <section class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-3xl border bg-background p-5">
                <div class="flex items-center gap-2">
                    <Ban class="h-5 w-5 text-rose-600" />
                    <h2 class="text-lg font-semibold">Block bookings</h2>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">
                    Close a whole local date or a precise time window.
                </p>
                <form class="mt-5 grid gap-3" @submit.prevent="submitBlock">
                    <Input v-model="blockForm.block_date" type="date" />
                    <label
                        class="flex items-center justify-between rounded-2xl bg-muted/50 p-3 text-sm font-medium"
                        >Full-day closure<input
                            v-model="blockForm.is_full_day"
                            type="checkbox"
                            class="h-5 w-5 accent-rose-600"
                    /></label>
                    <div
                        v-if="!blockForm.is_full_day"
                        class="grid grid-cols-2 gap-2"
                    >
                        <Input
                            v-model="blockForm.starts_at_time"
                            type="time"
                        /><Input v-model="blockForm.ends_at_time" type="time" />
                    </div>
                    <label
                        v-if="!blockForm.is_full_day"
                        class="flex items-center justify-between rounded-2xl bg-muted/50 p-3 text-sm font-medium"
                        >Ends next day<input
                            v-model="blockForm.ends_next_day"
                            type="checkbox"
                            class="h-5 w-5 accent-rose-600"
                    /></label>
                    <Input
                        v-model="blockForm.reason"
                        placeholder="Reason (optional)"
                    />
                    <Button type="submit" :disabled="blockForm.processing"
                        >Add booking block</Button
                    >
                </form>
                <div class="mt-5 space-y-2">
                    <div
                        v-for="block in turf.slot_blocks"
                        :key="block.id"
                        class="flex items-center justify-between gap-3 rounded-2xl border p-3"
                    >
                        <div>
                            <p class="text-sm font-semibold">
                                {{ block.block_date }} ·
                                {{
                                    block.is_full_day
                                        ? 'All day'
                                        : `${block.starts_at_time}–${block.ends_at_time}`
                                }}
                            </p>
                            <p
                                v-if="block.reason"
                                class="text-xs text-muted-foreground"
                            >
                                {{ block.reason }}
                            </p>
                        </div>
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            aria-label="Delete booking block"
                            @click="deleteItem(block.delete_url)"
                            ><Trash2 class="h-4 w-4"
                        /></Button>
                    </div>
                    <p
                        v-if="!turf.slot_blocks.length"
                        class="text-sm text-muted-foreground"
                    >
                        No booking blocks yet.
                    </p>
                </div>
            </div>
            <div class="rounded-3xl border bg-background p-5">
                <div class="flex items-center gap-2">
                    <Wrench class="h-5 w-5 text-amber-600" />
                    <h2 class="text-lg font-semibold">Maintenance</h2>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">
                    Times are interpreted in {{ turf.timezone }}.
                </p>
                <form
                    class="mt-5 grid gap-3"
                    @submit.prevent="submitMaintenance"
                >
                    <label
                        ><Label>Starts</Label
                        ><Input
                            v-model="maintenanceForm.starts_at_local"
                            type="datetime-local" /></label
                    ><label
                        ><Label>Ends</Label
                        ><Input
                            v-model="maintenanceForm.ends_at_local"
                            type="datetime-local" /></label
                    ><Input
                        v-model="maintenanceForm.reason"
                        placeholder="Work being completed"
                    /><Button
                        type="submit"
                        :disabled="maintenanceForm.processing"
                        >Schedule maintenance</Button
                    >
                </form>
                <div class="mt-5 space-y-2">
                    <div
                        v-for="block in turf.maintenance_blocks"
                        :key="block.id"
                        class="flex items-center justify-between gap-3 rounded-2xl border p-3"
                    >
                        <div>
                            <p class="text-sm font-semibold">
                                {{ block.starts_at_local.replace('T', ' ') }} →
                                {{ block.ends_at_local.replace('T', ' ') }}
                            </p>
                            <p
                                v-if="block.reason"
                                class="text-xs text-muted-foreground"
                            >
                                {{ block.reason }}
                            </p>
                        </div>
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            aria-label="Delete maintenance block"
                            @click="deleteItem(block.delete_url)"
                            ><Trash2 class="h-4 w-4"
                        /></Button>
                    </div>
                    <p
                        v-if="!turf.maintenance_blocks.length"
                        class="text-sm text-muted-foreground"
                    >
                        No maintenance blocks scheduled.
                    </p>
                </div>
            </div>
        </section>
        <section class="grid gap-4 md:grid-cols-2">
            <form
                class="rounded-3xl border bg-background p-5"
                @submit.prevent="configuration.put(routes.configuration)"
            >
                <div class="flex items-center gap-2">
                    <Clock3 class="h-5 w-5 text-emerald-700" />
                    <h2 class="text-lg font-semibold">Slot rules</h2>
                </div>
                <div class="mt-5 grid gap-4">
                    <label
                        ><Label>Slot duration (minutes)</Label
                        ><Input
                            v-model="
                                configuration.default_slot_duration_minutes
                            "
                            type="number"
                            min="15"
                            step="15" /></label
                    ><label
                        ><Label>Lead time (minutes)</Label
                        ><Input
                            v-model="configuration.booking_lead_time_minutes"
                            type="number"
                            min="0" /></label
                    ><label
                        ><Label>Advance days</Label
                        ><Input
                            v-model="configuration.advance_booking_window_days"
                            type="number"
                            min="1" /></label
                    ><Button type="submit" :disabled="configuration.processing"
                        >Save slot rules</Button
                    >
                </div>
            </form>
            <section class="rounded-3xl border bg-background p-5">
                <div class="flex items-center gap-2">
                    <CalendarDays class="h-5 w-5 text-emerald-700" />
                    <h2 class="text-lg font-semibold">Availability preview</h2>
                </div>
                <div class="mt-5 flex gap-2">
                    <Input v-model="date" type="date" /><Button
                        type="button"
                        :disabled="loadingSlots"
                        @click="loadSlots"
                        >{{ loadingSlots ? 'Loading' : 'Preview' }}</Button
                    >
                </div>
                <div class="mt-5 flex flex-wrap gap-2">
                    <span
                        v-for="slot in slots"
                        :key="slot.starts_at"
                        class="rounded-full bg-emerald-100 px-3 py-2 text-sm font-medium text-emerald-900"
                        >{{ slot.starts_at_time }}–{{ slot.ends_at_time }}</span
                    >
                    <p
                        v-if="slotError"
                        class="text-sm font-medium text-destructive"
                    >
                        {{ slotError }}
                    </p>
                    <p
                        v-else-if="
                            hasPreviewed && !loadingSlots && slots.length === 0
                        "
                        class="text-sm text-muted-foreground"
                    >
                        No bookable slots for this date.
                    </p>
                    <p
                        v-else-if="!hasPreviewed"
                        class="text-sm text-muted-foreground"
                    >
                        Choose a date to view bookable slots.
                    </p>
                </div>
            </section>
        </section>
    </main>
</template>
