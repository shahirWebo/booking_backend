<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    BellRing,
    Camera,
    ChevronLeft,
    CircleUserRound,
    LogOut,
    Mail,
    MapPin,
    Phone,
    Save,
    ShieldAlert,
    Trophy,
    X,
} from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { logout } from '@/routes';
import customer from '@/routes/customer';

type CustomerProfile = {
    id: number;
    user_id: number;
    name: string | null;
    mobile_number: string | null;
    email: string | null;
    profile_image_url: string | null;
    preferred_sport_ids: number[];
    default_location_label: string | null;
    email_notifications_enabled: boolean;
    sms_notifications_enabled: boolean;
    marketing_notifications_enabled: boolean;
    account_deletion_requested_at: string | null;
    account_deletion_reason: string | null;
};

type SportOption = {
    id: number;
    name: string;
    code: string;
};

type SharedUser = {
    name: string | null;
    avatar?: string | null;
};

const props = defineProps<{
    profile: CustomerProfile;
    availableSports: SportOption[];
}>();

const page = usePage<{ auth: { user: SharedUser | null } }>();

const profileForm = useForm({
    name: props.profile.name ?? '',
    email: props.profile.email ?? '',
    profile_image: null as File | null,
    remove_profile_image: false,
    preferred_sport_ids: [...props.profile.preferred_sport_ids],
    default_location_label: props.profile.default_location_label ?? '',
    email_notifications_enabled: props.profile.email_notifications_enabled,
    sms_notifications_enabled: props.profile.sms_notifications_enabled,
    marketing_notifications_enabled:
        props.profile.marketing_notifications_enabled,
});

const deletionForm = useForm({
    reason: props.profile.account_deletion_reason ?? '',
});

const avatarPreview = computed(() => {
    if (profileForm.profile_image instanceof File) {
        return URL.createObjectURL(profileForm.profile_image);
    }

    if (profileForm.remove_profile_image) {
        return null;
    }

    return (
        props.profile.profile_image_url ?? page.props.auth.user?.avatar ?? null
    );
});

const initials = computed(() => {
    const source = profileForm.name || props.profile.name || 'Customer Profile';

    return source
        .split(/\s+/)
        .map((segment) => segment.trim())
        .filter(Boolean)
        .slice(0, 2)
        .map((segment) => segment[0]?.toUpperCase() ?? '')
        .join('');
});

const selectedSports = computed(() =>
    props.availableSports.filter((sport) =>
        profileForm.preferred_sport_ids.includes(sport.id),
    ),
);

const deletionRequested = computed(
    () => props.profile.account_deletion_requested_at !== null,
);

function onProfileImageSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const [file] = input.files ?? [];

    profileForm.profile_image = file ?? null;
    profileForm.remove_profile_image = false;
}

function clearSelectedImage(): void {
    profileForm.profile_image = null;
    profileForm.remove_profile_image = true;
}

function toggleSport(sportId: number): void {
    if (profileForm.preferred_sport_ids.includes(sportId)) {
        profileForm.preferred_sport_ids =
            profileForm.preferred_sport_ids.filter(
                (currentSportId) => currentSportId !== sportId,
            );

        return;
    }

    profileForm.preferred_sport_ids = [
        ...profileForm.preferred_sport_ids,
        sportId,
    ].slice(0, 4);
}

function submitProfile(): void {
    profileForm
        .transform((data) => ({
            ...data,
            preferred_sport_ids: data.preferred_sport_ids,
            remove_profile_image: data.remove_profile_image ? '1' : '0',
            email_notifications_enabled: data.email_notifications_enabled
                ? '1'
                : '0',
            sms_notifications_enabled: data.sms_notifications_enabled
                ? '1'
                : '0',
            marketing_notifications_enabled:
                data.marketing_notifications_enabled ? '1' : '0',
        }))
        .put('/customer/profile', {
            preserveScroll: true,
            forceFormData: true,
        });
}

function submitDeletionRequest(): void {
    deletionForm.post('/customer/profile/deletion-request', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Customer Profile" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-5 pb-8">
        <section
            class="overflow-hidden rounded-[2rem] border border-white/70 bg-white/90 shadow-[0_30px_80px_-42px_rgba(15,23,42,0.4)] backdrop-blur"
        >
            <div
                class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,_rgba(16,185,129,0.16),_transparent_40%),linear-gradient(135deg,_rgba(255,255,255,0.96),_rgba(241,245,249,0.94))] px-5 py-5 sm:px-7 sm:py-6"
            >
                <div class="flex items-start justify-between gap-3">
                    <Link
                        :href="customer.home()"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/90 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        <ChevronLeft class="h-4 w-4" />
                        Customer home
                    </Link>

                    <span
                        class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-2 text-xs font-semibold tracking-[0.24em] text-emerald-900 uppercase"
                    >
                        <ShieldAlert class="h-4 w-4" />
                        Account settings
                    </span>
                </div>

                <div
                    class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div class="space-y-3">
                        <p
                            class="text-xs font-semibold tracking-[0.28em] text-slate-500 uppercase"
                        >
                            Customer identity
                        </p>
                        <div>
                            <h1
                                class="text-3xl font-semibold tracking-tight text-slate-950"
                            >
                                Profile and preferences
                            </h1>
                            <p
                                class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base"
                            >
                                Keep your booking identity, preferred sports,
                                and alerts in sync before checkout and future
                                booking reminders.
                            </p>
                        </div>
                    </div>

                    <Button
                        variant="outline"
                        type="button"
                        as-child
                        class="rounded-full"
                    >
                        <Link :href="logout()" as="button">
                            <LogOut class="h-4 w-4" />
                            Log out
                        </Link>
                    </Button>
                </div>
            </div>

            <div
                class="grid gap-4 px-5 py-5 sm:px-7 lg:grid-cols-[1.05fr_0.95fr]"
            >
                <form class="space-y-4" @submit.prevent="submitProfile">
                    <section
                        class="rounded-[1.75rem] border border-slate-200 bg-slate-50/85 p-4 shadow-sm"
                    >
                        <div class="flex items-start gap-4">
                            <div class="relative">
                                <div
                                    class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-[1.7rem] bg-slate-950 text-lg font-semibold tracking-[0.18em] text-white shadow-lg shadow-slate-900/15"
                                >
                                    <img
                                        v-if="avatarPreview"
                                        :src="avatarPreview"
                                        :alt="
                                            profileForm.name ||
                                            'Customer profile image'
                                        "
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-else>{{ initials }}</span>
                                </div>
                                <label
                                    class="absolute -right-2 -bottom-2 inline-flex cursor-pointer items-center justify-center rounded-full bg-white p-2 text-slate-700 shadow-md ring-1 ring-slate-200 transition hover:bg-slate-50"
                                >
                                    <Camera class="h-4 w-4" />
                                    <input
                                        class="hidden"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        @change="onProfileImageSelected"
                                    />
                                </label>
                            </div>

                            <div class="min-w-0 flex-1 space-y-3">
                                <div>
                                    <p
                                        class="text-xs font-semibold tracking-[0.24em] text-slate-500 uppercase"
                                    >
                                        Profile image
                                    </p>
                                    <p
                                        class="mt-2 text-sm leading-6 text-slate-600"
                                    >
                                        Upload a square photo so your account
                                        feels familiar across customer surfaces.
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        v-if="avatarPreview"
                                        type="button"
                                        variant="outline"
                                        class="rounded-full"
                                        @click="clearSelectedImage"
                                    >
                                        <X class="h-4 w-4" />
                                        Remove image
                                    </Button>
                                    <span
                                        class="text-xs leading-6 text-slate-500"
                                    >
                                        JPG, PNG, or WebP up to 3 MB.
                                    </span>
                                </div>
                                <InputError
                                    :message="profileForm.errors.profile_image"
                                />
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-700"
                            >
                                <CircleUserRound class="h-5 w-5" />
                            </div>
                            <div>
                                <p
                                    class="text-base font-semibold text-slate-950"
                                >
                                    Identity details
                                </p>
                                <p class="text-sm text-slate-500">
                                    These details appear during booking and
                                    follow-up messages.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4">
                            <div class="grid gap-2">
                                <Label for="customer-name">Full name</Label>
                                <Input
                                    id="customer-name"
                                    v-model="profileForm.name"
                                    name="name"
                                    placeholder="Your full name"
                                />
                                <InputError
                                    :message="profileForm.errors.name"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="customer-email"
                                    >Email address</Label
                                >
                                <Input
                                    id="customer-email"
                                    v-model="profileForm.email"
                                    type="email"
                                    name="email"
                                    placeholder="you@example.com"
                                />
                                <InputError
                                    :message="profileForm.errors.email"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="customer-mobile"
                                    >Mobile number</Label
                                >
                                <Input
                                    id="customer-mobile"
                                    :model-value="profile.mobile_number ?? ''"
                                    type="text"
                                    name="mobile_number"
                                    disabled
                                />
                                <p class="text-xs leading-5 text-slate-500">
                                    Mobile number changes still follow the OTP
                                    authentication flow.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700"
                            >
                                <Trophy class="h-5 w-5" />
                            </div>
                            <div>
                                <p
                                    class="text-base font-semibold text-slate-950"
                                >
                                    Preferred sports
                                </p>
                                <p class="text-sm text-slate-500">
                                    Pick up to four sports so future discovery
                                    and offers can stay relevant.
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="sport in availableSports"
                                :key="sport.id"
                                type="button"
                                class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-medium transition"
                                :class="
                                    profileForm.preferred_sport_ids.includes(
                                        sport.id,
                                    )
                                        ? 'border-emerald-500 bg-emerald-500 text-white shadow-sm'
                                        : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100'
                                "
                                @click="toggleSport(sport.id)"
                            >
                                {{ sport.name }}
                            </button>
                        </div>
                        <InputError
                            :message="profileForm.errors.preferred_sport_ids"
                        />
                    </section>

                    <section
                        class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-100 text-sky-700"
                            >
                                <MapPin class="h-5 w-5" />
                            </div>
                            <div>
                                <p
                                    class="text-base font-semibold text-slate-950"
                                >
                                    Default location preference
                                </p>
                                <p class="text-sm text-slate-500">
                                    Save the area you usually book around so
                                    discovery can feel faster.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="default-location-label"
                                >Preferred area</Label
                            >
                            <Input
                                id="default-location-label"
                                v-model="profileForm.default_location_label"
                                name="default_location_label"
                                placeholder="Example: Bandra West"
                            />
                            <InputError
                                :message="
                                    profileForm.errors.default_location_label
                                "
                            />
                        </div>
                    </section>

                    <section
                        class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-700"
                            >
                                <BellRing class="h-5 w-5" />
                            </div>
                            <div>
                                <p
                                    class="text-base font-semibold text-slate-950"
                                >
                                    Notification preferences
                                </p>
                                <p class="text-sm text-slate-500">
                                    Control booking updates, reminders, and
                                    occasional marketing.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label
                                class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"
                            >
                                <input
                                    v-model="
                                        profileForm.email_notifications_enabled
                                    "
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-sky-500"
                                />
                                <span>
                                    <span
                                        class="block font-medium text-slate-900"
                                        >Email booking updates</span
                                    >
                                    <span
                                        class="block text-sm leading-6 text-slate-500"
                                    >
                                        Receipts, changes, and account notices
                                        by email.
                                    </span>
                                </span>
                            </label>

                            <label
                                class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"
                            >
                                <input
                                    v-model="
                                        profileForm.sms_notifications_enabled
                                    "
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-sky-500"
                                />
                                <span>
                                    <span
                                        class="block font-medium text-slate-900"
                                        >SMS reminders</span
                                    >
                                    <span
                                        class="block text-sm leading-6 text-slate-500"
                                    >
                                        Pre-booking reminders and time-sensitive
                                        notices on mobile.
                                    </span>
                                </span>
                            </label>

                            <label
                                class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"
                            >
                                <input
                                    v-model="
                                        profileForm.marketing_notifications_enabled
                                    "
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-sky-500"
                                />
                                <span>
                                    <span
                                        class="block font-medium text-slate-900"
                                        >Offers and launches</span
                                    >
                                    <span
                                        class="block text-sm leading-6 text-slate-500"
                                    >
                                        New venues, sports launches, and
                                        promotional announcements.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </section>

                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <Button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="rounded-full"
                        >
                            <Save class="h-4 w-4" />
                            Save profile
                        </Button>
                        <p class="text-sm leading-6 text-slate-500">
                            Selected sports:
                            {{
                                selectedSports.length
                                    ? selectedSports
                                          .map((sport) => sport.name)
                                          .join(', ')
                                    : 'None yet'
                            }}
                        </p>
                    </div>
                </form>

                <aside class="space-y-4">
                    <article
                        class="rounded-[1.75rem] bg-slate-950 p-5 text-slate-50 shadow-lg"
                    >
                        <p
                            class="text-xs font-semibold tracking-[0.24em] text-sky-300 uppercase"
                        >
                            Account snapshot
                        </p>
                        <dl class="mt-4 space-y-4">
                            <div class="flex items-start gap-3">
                                <Mail class="mt-0.5 h-4 w-4 text-sky-300" />
                                <div>
                                    <dt
                                        class="text-xs text-slate-400 uppercase"
                                    >
                                        Email
                                    </dt>
                                    <dd class="mt-1 text-sm text-slate-100">
                                        {{
                                            profileForm.email || 'Not added yet'
                                        }}
                                    </dd>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <Phone class="mt-0.5 h-4 w-4 text-sky-300" />
                                <div>
                                    <dt
                                        class="text-xs text-slate-400 uppercase"
                                    >
                                        Mobile
                                    </dt>
                                    <dd class="mt-1 text-sm text-slate-100">
                                        {{
                                            profile.mobile_number ||
                                            'Not added yet'
                                        }}
                                    </dd>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <MapPin class="mt-0.5 h-4 w-4 text-sky-300" />
                                <div>
                                    <dt
                                        class="text-xs text-slate-400 uppercase"
                                    >
                                        Preferred area
                                    </dt>
                                    <dd class="mt-1 text-sm text-slate-100">
                                        {{
                                            profileForm.default_location_label ||
                                            'Not added yet'
                                        }}
                                    </dd>
                                </div>
                            </div>
                        </dl>
                    </article>

                    <article
                        class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <p
                            class="text-xs font-semibold tracking-[0.24em] text-slate-500 uppercase"
                        >
                            Delete-account settings
                        </p>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            This flow creates a request for follow-up instead of
                            deleting your account immediately, which keeps
                            active booking history safe.
                        </p>

                        <div
                            v-if="deletionRequested"
                            class="mt-4 rounded-[1.5rem] border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
                        >
                            <p class="font-medium">Request already submitted</p>
                            <p class="mt-1 leading-6">
                                Submitted on
                                {{ profile.account_deletion_requested_at }}.
                            </p>
                        </div>

                        <form
                            class="mt-4 space-y-3"
                            @submit.prevent="submitDeletionRequest"
                        >
                            <div class="grid gap-2">
                                <Label for="deletion-reason">Reason</Label>
                                <textarea
                                    id="deletion-reason"
                                    v-model="deletionForm.reason"
                                    rows="4"
                                    class="min-h-28 w-full rounded-[1.3rem] border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-900 outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    placeholder="Tell us why you want to remove your customer account."
                                    :disabled="deletionRequested"
                                />
                                <InputError
                                    :message="deletionForm.errors.reason"
                                />
                            </div>

                            <Button
                                type="submit"
                                variant="outline"
                                class="w-full rounded-full border-red-200 text-red-700 hover:bg-red-50"
                                :disabled="
                                    deletionForm.processing || deletionRequested
                                "
                            >
                                Request account deletion
                            </Button>
                        </form>
                    </article>
                </aside>
            </div>
        </section>
    </div>
</template>
