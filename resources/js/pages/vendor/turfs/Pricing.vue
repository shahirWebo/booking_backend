<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    Clock3,
    Coins,
    LoaderCircle,
    Pencil,
    Plus,
    Save,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type RuleType = 'base' | 'weekday' | 'weekend' | 'peak_hour' | 'special_date';
type PricingRule = {
    id: number;
    rule_type: RuleType;
    price_minor: number;
    price: string;
    currency: string;
    priority: number;
    effective_from_date: string | null;
    effective_until_date: string | null;
    weekday: number | null;
    special_date: string | null;
    starts_at_time: string | null;
    ends_at_time: string | null;
    ends_next_day: boolean;
    is_active: boolean;
    update_url: string;
    delete_url: string;
};
type AvailableSlot = {
    starts_at: string;
    ends_at: string;
    starts_at_time: string;
    ends_at_time: string;
};
type Quote = {
    total_minor: number;
    currency: string;
    slots: Array<{ price_minor: number; pricing_rule_id: number }>;
};
type RuleFormData = Omit<
    PricingRule,
    'id' | 'price_minor' | 'update_url' | 'delete_url'
>;

const props = defineProps<{
    turf: {
        id: number;
        name: string;
        location_name: string;
        timezone: string;
        default_slot_duration_minutes: number;
    };
    pricing_rules: PricingRule[];
    routes: {
        back: string;
        availability: string;
        store: string;
        slots: string;
        quote: string;
    };
}>();

const editorTypes: Array<{ value: RuleType; label: string }> = [
    { value: 'base', label: 'Base' },
    { value: 'weekday', label: 'Weekday' },
    { value: 'weekend', label: 'Weekend' },
    { value: 'peak_hour', label: 'Peak hours' },
    { value: 'special_date', label: 'Special date' },
];
const weekdayLabels = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday',
];
const today = new Date().toISOString().slice(0, 10);
const selectedType = ref<RuleType>('base');
const editingRule = ref<PricingRule | null>(null);
const previewDate = ref(today);
const slots = ref<AvailableSlot[]>([]);
const selectedSlots = ref<AvailableSlot[]>([]);
const previewed = ref(false);
const loadingSlots = ref(false);
const loadingQuote = ref(false);
const previewError = ref('');
const quote = ref<Quote | null>(null);

function emptyRule(type: RuleType): RuleFormData {
    return {
        rule_type: type,
        price: '100.00',
        currency: 'INR',
        priority: type === 'base' ? 100 : 50,
        effective_from_date: null,
        effective_until_date: null,
        weekday: type === 'weekday' ? 1 : null,
        special_date: type === 'special_date' ? previewDate.value : null,
        starts_at_time: type === 'peak_hour' ? '18:00' : null,
        ends_at_time: type === 'peak_hour' ? '21:00' : null,
        ends_next_day: false,
        is_active: true,
    };
}

const ruleForm = useForm(emptyRule('base'));
const rulesByType = computed(() => {
    return editorTypes.map(({ value, label }) => ({
        label,
        rules: props.pricing_rules.filter((rule) => rule.rule_type === value),
    }));
});
const previewDays = computed(() => {
    const start = new Date(`${previewDate.value}T12:00:00`);

    return Array.from({ length: 7 }, (_, offset) => {
        const date = new Date(start);
        date.setDate(start.getDate() + offset);

        return {
            value: date.toISOString().slice(0, 10),
            weekday: date.toLocaleDateString(undefined, { weekday: 'short' }),
            day: date.getDate(),
        };
    });
});

function payload(): RuleFormData {
    return {
        rule_type: ruleForm.rule_type,
        price: ruleForm.price,
        currency: 'INR',
        priority: Number(ruleForm.priority),
        effective_from_date: ruleForm.effective_from_date || null,
        effective_until_date: ruleForm.effective_until_date || null,
        weekday:
            ruleForm.rule_type === 'weekday' ? Number(ruleForm.weekday) : null,
        special_date:
            ruleForm.rule_type === 'special_date'
                ? ruleForm.special_date
                : null,
        starts_at_time:
            ruleForm.rule_type === 'peak_hour' ? ruleForm.starts_at_time : null,
        ends_at_time:
            ruleForm.rule_type === 'peak_hour' ? ruleForm.ends_at_time : null,
        ends_next_day:
            ruleForm.rule_type === 'peak_hour' && ruleForm.ends_next_day,
        is_active: ruleForm.is_active,
    };
}

function selectType(type: RuleType): void {
    selectedType.value = type;
    editingRule.value = null;
    Object.assign(ruleForm, emptyRule(type));
    ruleForm.clearErrors();
}

function editRule(rule: PricingRule): void {
    selectedType.value = rule.rule_type;
    editingRule.value = rule;
    Object.assign(ruleForm, {
        ...rule,
        starts_at_time: rule.starts_at_time?.slice(0, 5) ?? null,
        ends_at_time: rule.ends_at_time?.slice(0, 5) ?? null,
    });
    ruleForm.clearErrors();
}

function saveRule(): void {
    if (editingRule.value) {
        router.put(editingRule.value.update_url, payload(), {
            preserveScroll: true,
            onSuccess: () => selectType(selectedType.value),
        });

        return;
    }

    ruleForm.post(props.routes.store, {
        preserveScroll: true,
        onSuccess: () => selectType(selectedType.value),
    });
}

function deleteRule(rule: PricingRule): void {
    router.delete(rule.delete_url, { preserveScroll: true });
}

function selectPreviewDay(date: string): void {
    previewDate.value = date;
    selectedSlots.value = [];
    quote.value = null;
}

function toggleSlot(slot: AvailableSlot): void {
    const index = selectedSlots.value.findIndex(
        (selected) => selected.starts_at === slot.starts_at,
    );

    if (index >= 0) {
        selectedSlots.value.splice(index, 1);
    } else {
        selectedSlots.value.push(slot);
    }

    quote.value = null;
}

function isSelected(slot: AvailableSlot): boolean {
    return selectedSlots.value.some(
        (selected) => selected.starts_at === slot.starts_at,
    );
}

function formatInrAmount(priceMinor: number): string {
    return `${Math.trunc(priceMinor / 100).toLocaleString('en-IN')}.${String(
        priceMinor % 100,
    ).padStart(2, '0')}`;
}

async function loadSlots(): Promise<void> {
    loadingSlots.value = true;
    previewed.value = true;
    previewError.value = '';
    selectedSlots.value = [];
    quote.value = null;

    try {
        const response = await fetch(
            `${props.routes.slots}?date=${previewDate.value}`,
            {
                headers: { Accept: 'application/json' },
            },
        );

        if (!response.ok) {
            throw new Error('Unable to load availability for this date.');
        }

        const payload = (await response.json()) as { slots?: AvailableSlot[] };
        slots.value = payload.slots ?? [];
    } catch (error) {
        slots.value = [];
        previewError.value =
            error instanceof Error
                ? error.message
                : 'Unable to load availability.';
    } finally {
        loadingSlots.value = false;
    }
}

async function calculateQuote(): Promise<void> {
    if (selectedSlots.value.length === 0) {
        return;
    }

    loadingQuote.value = true;
    previewError.value = '';

    try {
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');
        const response = await fetch(props.routes.quote, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            body: JSON.stringify({
                slots: selectedSlots.value.map(({ starts_at, ends_at }) => ({
                    starts_at,
                    ends_at,
                })),
            }),
        });

        const payload = (await response.json()) as {
            quote?: Quote;
            message?: string;
        };

        if (!response.ok || !payload.quote) {
            throw new Error(
                payload.message ?? 'Unable to calculate this price.',
            );
        }

        quote.value = payload.quote;
    } catch (error) {
        quote.value = null;
        previewError.value =
            error instanceof Error
                ? error.message
                : 'Unable to calculate this price.';
    } finally {
        loadingQuote.value = false;
    }
}
</script>

<template>
    <Head :title="`Pricing · ${turf.name}`" />
    <main class="mx-auto flex w-full max-w-5xl flex-col gap-4 p-4 pb-10">
        <section
            class="overflow-hidden rounded-3xl bg-slate-950 p-5 text-white"
        >
            <Link
                :href="routes.back"
                class="inline-flex items-center gap-2 text-sm text-slate-300"
            >
                <ArrowLeft class="h-4 w-4" /> Turf details
            </Link>
            <div class="mt-6 flex items-end justify-between gap-4">
                <div>
                    <p
                        class="text-xs font-semibold tracking-[.2em] text-amber-300 uppercase"
                    >
                        {{ turf.location_name }} · {{ turf.timezone }}
                    </p>
                    <h1 class="mt-2 text-3xl font-semibold">
                        Price with intent
                    </h1>
                    <p class="mt-2 max-w-xl text-sm text-slate-300">
                        Set the default first, then layer peak, weekend, or
                        calendar exceptions above it.
                    </p>
                </div>
                <Coins class="h-12 w-12 text-amber-300/80" />
            </div>
        </section>

        <section class="rounded-3xl border bg-background p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">Build a price rule</h2>
                    <p class="text-sm text-muted-foreground">
                        Lower priorities win. Equal priorities favor the most
                        specific matching rule.
                    </p>
                </div>
                <Link
                    :href="routes.availability"
                    class="text-sm font-medium text-emerald-700 underline-offset-4 hover:underline"
                >
                    Check availability
                </Link>
            </div>

            <div class="mt-5 flex gap-2 overflow-x-auto pb-1">
                <button
                    v-for="type in editorTypes"
                    :key="type.value"
                    type="button"
                    class="shrink-0 rounded-full border px-4 py-2 text-sm font-semibold transition-colors"
                    :class="
                        selectedType === type.value
                            ? 'border-amber-400 bg-amber-100 text-amber-950'
                            : 'bg-background text-muted-foreground'
                    "
                    @click="selectType(type.value)"
                >
                    {{ type.label }}
                </button>
            </div>

            <form class="mt-5 grid gap-4" @submit.prevent="saveRule">
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label for="price">Price per slot</Label>
                        <Input
                            id="price"
                            v-model="ruleForm.price"
                            min="0"
                            type="number"
                            step="0.01"
                            inputmode="numeric"
                        />
                        <p class="text-xs text-muted-foreground">
                            Enter the amount vendors charge, for example `1200`
                            for Rs 1,200.
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="currency">Currency</Label>
                        <Input id="currency" model-value="INR" readonly />
                    </div>
                </div>

                <div v-if="selectedType === 'weekday'" class="grid gap-2">
                    <Label for="weekday">Applies on</Label>
                    <select
                        id="weekday"
                        v-model="ruleForm.weekday"
                        class="h-10 rounded-md border bg-background px-3 text-sm"
                    >
                        <option
                            v-for="(label, index) in weekdayLabels"
                            :key="label"
                            :value="index + 1"
                        >
                            {{ label }}
                        </option>
                    </select>
                </div>

                <div v-if="selectedType === 'special_date'" class="grid gap-2">
                    <Label for="special-date">Special local date</Label>
                    <Input
                        id="special-date"
                        :model-value="ruleForm.special_date ?? ''"
                        type="date"
                        @update:model-value="
                            ruleForm.special_date = $event
                                ? String($event)
                                : null
                        "
                    />
                </div>

                <div
                    v-if="selectedType === 'peak_hour'"
                    class="grid gap-3 rounded-2xl bg-amber-50 p-4"
                >
                    <div
                        class="flex items-center gap-2 text-sm font-semibold text-amber-950"
                    >
                        <Clock3 class="h-4 w-4" /> Peak window
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label for="starts-at">Starts</Label
                            ><Input
                                id="starts-at"
                                :model-value="ruleForm.starts_at_time ?? ''"
                                type="time"
                                @update:model-value="
                                    ruleForm.starts_at_time = $event
                                        ? String($event)
                                        : null
                                "
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ends-at">Ends</Label
                            ><Input
                                id="ends-at"
                                :model-value="ruleForm.ends_at_time ?? ''"
                                type="time"
                                @update:model-value="
                                    ruleForm.ends_at_time = $event
                                        ? String($event)
                                        : null
                                "
                            />
                        </div>
                    </div>
                    <label
                        class="flex items-center gap-3 text-sm font-medium text-amber-950"
                        ><input
                            v-model="ruleForm.ends_next_day"
                            type="checkbox"
                            class="h-4 w-4 accent-amber-600"
                        />
                        Ends the next day</label
                    >
                </div>

                <details class="rounded-2xl border p-4">
                    <summary class="cursor-pointer text-sm font-semibold">
                        Fine tune priority and effective dates
                    </summary>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="priority">Priority</Label
                            ><Input
                                id="priority"
                                v-model="ruleForm.priority"
                                min="1"
                                type="number"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="effective-from">From</Label
                            ><Input
                                id="effective-from"
                                :model-value="
                                    ruleForm.effective_from_date ?? ''
                                "
                                type="date"
                                @update:model-value="
                                    ruleForm.effective_from_date = $event
                                        ? String($event)
                                        : null
                                "
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="effective-until">Until</Label
                            ><Input
                                id="effective-until"
                                :model-value="
                                    ruleForm.effective_until_date ?? ''
                                "
                                type="date"
                                @update:model-value="
                                    ruleForm.effective_until_date = $event
                                        ? String($event)
                                        : null
                                "
                            />
                        </div>
                    </div>
                </details>

                <p
                    v-if="Object.keys(ruleForm.errors).length"
                    class="text-sm text-destructive"
                >
                    {{ Object.values(ruleForm.errors)[0] }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <Button :disabled="ruleForm.processing" type="submit"
                        ><Save class="h-4 w-4" />
                        {{ editingRule ? 'Update rule' : 'Add rule' }}</Button
                    >
                    <Button
                        v-if="editingRule"
                        type="button"
                        variant="outline"
                        @click="selectType(selectedType)"
                        >Cancel</Button
                    >
                </div>
            </form>
        </section>

        <section class="rounded-3xl border bg-background p-5">
            <div class="flex items-center gap-2">
                <Pencil class="h-5 w-5 text-emerald-700" />
                <h2 class="text-lg font-semibold">Active rulebook</h2>
            </div>
            <div
                v-if="pricing_rules.length"
                class="mt-4 grid gap-3 md:grid-cols-2"
            >
                <article
                    v-for="group in rulesByType"
                    v-show="group.rules.length"
                    :key="group.label"
                    class="rounded-2xl border p-4"
                >
                    <p
                        class="text-xs font-semibold tracking-[.16em] text-muted-foreground uppercase"
                    >
                        {{ group.label }}
                    </p>
                    <div
                        v-for="rule in group.rules"
                        :key="rule.id"
                        class="mt-3 flex items-center justify-between gap-3 border-t pt-3 first:border-0 first:pt-0"
                    >
                        <div>
                            <p class="font-semibold">Rs {{ rule.price }}</p>
                            <p class="text-xs text-muted-foreground">
                                Priority {{ rule.priority
                                }}<span v-if="rule.starts_at_time">
                                    · {{ rule.starts_at_time.slice(0, 5) }}-{{
                                        rule.ends_at_time?.slice(0, 5)
                                    }}</span
                                ><span v-if="rule.special_date">
                                    · {{ rule.special_date }}</span
                                >
                            </p>
                        </div>
                        <div class="flex gap-1">
                            <Button
                                size="icon"
                                type="button"
                                variant="ghost"
                                :aria-label="`Edit rule ${rule.id}`"
                                @click="editRule(rule)"
                                ><Pencil class="h-4 w-4" /></Button
                            ><Button
                                size="icon"
                                type="button"
                                variant="ghost"
                                :aria-label="`Delete rule ${rule.id}`"
                                @click="deleteRule(rule)"
                                ><Trash2 class="h-4 w-4 text-rose-600"
                            /></Button>
                        </div>
                    </div>
                </article>
            </div>
            <div
                v-else
                class="mt-4 rounded-2xl bg-muted/50 p-4 text-sm text-muted-foreground"
            >
                Start with a base price so every available slot has a clear
                default.
            </div>
        </section>

        <section class="rounded-3xl border bg-background p-5">
            <div class="flex items-center gap-2">
                <CalendarDays class="h-5 w-5 text-violet-700" />
                <div>
                    <h2 class="text-lg font-semibold">
                        Pricing preview calendar
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Choose a local date, select available slots, then
                        calculate the server price.
                    </p>
                </div>
            </div>
            <div class="mt-5 flex gap-2 overflow-x-auto pb-1">
                <button
                    v-for="day in previewDays"
                    :key="day.value"
                    type="button"
                    class="grid min-w-14 place-items-center rounded-2xl border px-3 py-2 text-sm"
                    :class="
                        previewDate === day.value
                            ? 'border-violet-500 bg-violet-100 text-violet-950'
                            : 'bg-background'
                    "
                    @click="selectPreviewDay(day.value)"
                >
                    <span class="text-xs">{{ day.weekday }}</span
                    ><strong>{{ day.day }}</strong>
                </button>
                <Input
                    v-model="previewDate"
                    type="date"
                    class="min-w-40"
                    @change="selectPreviewDay(previewDate)"
                />
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="loadingSlots"
                    @click="loadSlots"
                    ><LoaderCircle
                        v-if="loadingSlots"
                        class="h-4 w-4 animate-spin"
                    /><CalendarDays v-else class="h-4 w-4" /> Load slots</Button
                ><span class="text-sm text-muted-foreground"
                    >{{ turf.default_slot_duration_minutes }}-minute local
                    slots</span
                >
            </div>
            <div v-if="previewed" class="mt-4">
                <div v-if="slots.length" class="flex flex-wrap gap-2">
                    <button
                        v-for="slot in slots"
                        :key="slot.starts_at"
                        type="button"
                        class="rounded-xl border px-3 py-2 text-sm font-medium"
                        :class="
                            isSelected(slot)
                                ? 'border-emerald-500 bg-emerald-100 text-emerald-950'
                                : 'bg-background'
                        "
                        @click="toggleSlot(slot)"
                    >
                        {{ slot.starts_at_time.slice(0, 5) }}-{{
                            slot.ends_at_time.slice(0, 5)
                        }}
                    </button>
                </div>
                <p
                    v-else-if="!loadingSlots"
                    class="rounded-2xl bg-muted/50 p-4 text-sm text-muted-foreground"
                >
                    No bookable slots are available for this local date.
                </p>
            </div>
            <div
                v-if="selectedSlots.length"
                class="mt-5 rounded-2xl bg-slate-950 p-4 text-white"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-300">
                            {{ selectedSlots.length }} selected slot{{
                                selectedSlots.length === 1 ? '' : 's'
                            }}
                        </p>
                        <p v-if="quote" class="mt-1 text-2xl font-semibold">
                            Rs {{ formatInrAmount(quote.total_minor) }}
                        </p>
                    </div>
                    <Button
                        type="button"
                        :disabled="loadingQuote"
                        class="bg-amber-300 text-amber-950 hover:bg-amber-200"
                        @click="calculateQuote"
                        ><LoaderCircle
                            v-if="loadingQuote"
                            class="h-4 w-4 animate-spin"
                        /><Plus v-else class="h-4 w-4" /> Calculate</Button
                    >
                </div>
            </div>
            <p v-if="previewError" class="mt-3 text-sm text-destructive">
                {{ previewError }}
            </p>
        </section>
    </main>
</template>
