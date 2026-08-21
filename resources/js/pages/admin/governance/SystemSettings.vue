<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Clock3, Cog, MessageSquareText, Save, TimerReset } from '@lucide/vue';
import FormFeedback from '@/components/feedback/FormFeedback.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';

type SystemSettings = {
    booking: {
        booking_hold_minutes: number;
        cancellation_cutoff_hours: number;
        max_advance_booking_days: number;
        min_slot_duration_minutes: number;
        max_booking_duration_minutes: number;
    };
    otp: {
        code_lifetime_seconds: number;
        resend_cooldown_seconds: number;
        max_verification_attempts: number;
    };
    support: {
        support_email: string;
        support_phone_e164: string;
        support_hours: string;
        support_timezone: string;
    };
};

const props = defineProps<{
    settings: SystemSettings;
    routes: {
        update: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            {
                title: 'System Settings',
                href: admin.system_settings.show(),
            },
        ],
    },
});

const form = useForm({
    booking: {
        ...props.settings.booking,
    },
    otp: {
        ...props.settings.otp,
    },
    support: {
        ...props.settings.support,
    },
});

const errorFor = (field: string) =>
    (form.errors as Record<string, string | undefined>)[field];

function submitForm(): void {
    form.put(props.routes.update, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="System Settings" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <section
            class="overflow-hidden rounded-3xl border border-sidebar-border/70 bg-sidebar-accent/40 p-5 dark:border-sidebar-border"
        >
            <div class="space-y-3">
                <p
                    class="text-xs font-semibold tracking-[0.24em] text-sidebar-foreground/60 uppercase"
                >
                    Admin governance
                </p>
                <h1
                    class="text-2xl font-semibold tracking-tight text-sidebar-foreground"
                >
                    System settings
                </h1>
                <p
                    class="max-w-3xl text-sm leading-6 text-sidebar-foreground/70"
                >
                    Manage the protected booking, OTP, and support defaults from
                    the sidebar-inset admin workspace.
                </p>
            </div>
        </section>

        <form class="grid gap-4 xl:grid-cols-3" @submit.prevent="submitForm">
            <section
                class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-muted/70"
                    >
                        <Clock3 class="h-5 w-5 text-muted-foreground" />
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.24em] text-muted-foreground uppercase"
                        >
                            Booking
                        </p>
                        <h2 class="mt-2 text-xl font-semibold tracking-tight">
                            Reservation timing
                        </h2>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="grid gap-2">
                        <Label for="booking-hold">Booking hold minutes</Label>
                        <Input
                            id="booking-hold"
                            v-model="form.booking.booking_hold_minutes"
                            type="number"
                            min="1"
                            max="60"
                        />
                        <InputError
                            :message="errorFor('booking.booking_hold_minutes')"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="booking-cancel"
                            >Cancellation cutoff hours</Label
                        >
                        <Input
                            id="booking-cancel"
                            v-model="form.booking.cancellation_cutoff_hours"
                            type="number"
                            min="0"
                            max="168"
                        />
                        <InputError
                            :message="
                                errorFor('booking.cancellation_cutoff_hours')
                            "
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="booking-advance"
                            >Max advance booking days</Label
                        >
                        <Input
                            id="booking-advance"
                            v-model="form.booking.max_advance_booking_days"
                            type="number"
                            min="1"
                            max="365"
                        />
                        <InputError
                            :message="
                                errorFor('booking.max_advance_booking_days')
                            "
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="booking-min-slot"
                            >Minimum slot duration minutes</Label
                        >
                        <Input
                            id="booking-min-slot"
                            v-model="form.booking.min_slot_duration_minutes"
                            type="number"
                            min="30"
                            max="240"
                        />
                        <InputError
                            :message="
                                errorFor('booking.min_slot_duration_minutes')
                            "
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="booking-max-duration"
                            >Maximum booking duration minutes</Label
                        >
                        <Input
                            id="booking-max-duration"
                            v-model="form.booking.max_booking_duration_minutes"
                            type="number"
                            min="30"
                            max="600"
                        />
                        <InputError
                            :message="
                                errorFor('booking.max_booking_duration_minutes')
                            "
                        />
                    </div>
                </div>
            </section>

            <section
                class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-muted/70"
                    >
                        <TimerReset class="h-5 w-5 text-muted-foreground" />
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.24em] text-muted-foreground uppercase"
                        >
                            OTP
                        </p>
                        <h2 class="mt-2 text-xl font-semibold tracking-tight">
                            Verification windows
                        </h2>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="grid gap-2">
                        <Label for="otp-lifetime">Code lifetime seconds</Label>
                        <Input
                            id="otp-lifetime"
                            v-model="form.otp.code_lifetime_seconds"
                            type="number"
                            min="60"
                            max="900"
                        />
                        <InputError
                            :message="errorFor('otp.code_lifetime_seconds')"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="otp-resend">Resend cooldown seconds</Label>
                        <Input
                            id="otp-resend"
                            v-model="form.otp.resend_cooldown_seconds"
                            type="number"
                            min="30"
                            max="300"
                        />
                        <InputError
                            :message="errorFor('otp.resend_cooldown_seconds')"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="otp-attempts"
                            >Max verification attempts</Label
                        >
                        <Input
                            id="otp-attempts"
                            v-model="form.otp.max_verification_attempts"
                            type="number"
                            min="1"
                            max="10"
                        />
                        <InputError
                            :message="errorFor('otp.max_verification_attempts')"
                        />
                    </div>
                </div>
            </section>

            <section
                class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-muted/70"
                    >
                        <MessageSquareText
                            class="h-5 w-5 text-muted-foreground"
                        />
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.24em] text-muted-foreground uppercase"
                        >
                            Support
                        </p>
                        <h2 class="mt-2 text-xl font-semibold tracking-tight">
                            Public support details
                        </h2>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="grid gap-2">
                        <Label for="support-email">Support email</Label>
                        <Input
                            id="support-email"
                            v-model="form.support.support_email"
                            type="email"
                        />
                        <InputError
                            :message="errorFor('support.support_email')"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="support-phone">Support phone E.164</Label>
                        <Input
                            id="support-phone"
                            v-model="form.support.support_phone_e164"
                        />
                        <InputError
                            :message="errorFor('support.support_phone_e164')"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="support-hours">Support hours</Label>
                        <Input
                            id="support-hours"
                            v-model="form.support.support_hours"
                        />
                        <InputError
                            :message="errorFor('support.support_hours')"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="support-timezone">Support timezone</Label>
                        <Input
                            id="support-timezone"
                            v-model="form.support.support_timezone"
                        />
                        <InputError
                            :message="errorFor('support.support_timezone')"
                        />
                    </div>
                </div>
            </section>

            <div class="xl:col-span-3">
                <FormFeedback
                    v-if="form.hasErrors"
                    message="Please fix the highlighted system settings fields."
                    variant="error"
                />

                <section
                    class="mt-4 rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
                >
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-start gap-3">
                            <Cog class="mt-0.5 h-5 w-5 text-muted-foreground" />
                            <p
                                class="max-w-2xl text-sm leading-6 text-muted-foreground"
                            >
                                These values affect the shared operational
                                defaults across booking, authentication, and
                                customer support.
                            </p>
                        </div>

                        <Button type="submit" :disabled="form.processing">
                            <Save class="h-4 w-4" />
                            Save settings
                        </Button>
                    </div>
                </section>
            </div>
        </form>
    </div>
</template>
