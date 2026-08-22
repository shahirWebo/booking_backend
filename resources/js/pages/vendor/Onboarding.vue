<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowRight,
    BadgeCheck,
    Building2,
    Clock3,
    FileCheck2,
    Landmark,
    PhoneCall,
    RefreshCcw,
    ShieldAlert,
    Upload,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { showToast } from '@/lib/toast';
import vendorRoutes from '@/routes/vendor';

type VendorDocument = {
    id: number;
    document_type: string;
    status: string;
    file_status: string | null;
};

type VendorBankAccount = {
    id: number;
    bank_name: string;
    account_number_last_four: string;
    country_code: string;
    currency: string;
    status: string;
};

type DocumentTypeOption = {
    value: string;
    label: string;
};

type DocumentUploadState = {
    description: string;
    label: string;
    tone: 'ready' | 'processing' | 'attention' | 'missing';
};

const props = defineProps<{
    vendor: {
        id: number;
        status: string;
        legal_name: string | null;
        display_name: string | null;
        legal_entity_type: string | null;
        primary_contact_name: string | null;
        primary_contact_email: string | null;
        primary_contact_mobile_number: string | null;
        is_gst_registered: boolean | null;
        gstin: string | null;
        submission_version: number;
        can_edit: boolean;
    };
    owner: {
        name: string | null;
        mobile_number: string | null;
        email: string | null;
    };
    kycDocuments: VendorDocument[];
    bankAccounts: VendorBankAccount[];
    documentTypes: DocumentTypeOption[];
    rejection: {
        reason_code: string | null;
        reason_message: string | null;
        transitioned_at: string;
    } | null;
    routes: {
        business_details: string;
        primary_contact: string;
        gst_details: string;
        kyc_documents: string;
        bank_accounts: string;
        submit: string;
        prepare_resubmission: string;
    };
}>();

const businessForm = useForm({
    legal_name: props.vendor.legal_name ?? '',
    display_name: props.vendor.display_name ?? '',
    legal_entity_type: props.vendor.legal_entity_type ?? '',
});

const contactForm = useForm({
    primary_contact_name:
        props.vendor.primary_contact_name ?? props.owner.name ?? '',
    primary_contact_email:
        props.vendor.primary_contact_email ?? props.owner.email ?? '',
    primary_contact_mobile_number:
        props.vendor.primary_contact_mobile_number ??
        props.owner.mobile_number ??
        '',
});

const gstForm = useForm({
    is_gst_registered: props.vendor.is_gst_registered ?? false,
    gstin: props.vendor.gstin ?? '',
});

const kycForm = useForm({
    document_type: props.documentTypes[0]?.value ?? 'identity_proof',
    document: null as File | null,
});
const documentInput = ref<HTMLInputElement | null>(null);
const locallyUploadedDocumentTypes = ref<Set<string>>(new Set());

const bankForm = useForm({
    account_holder_name: props.vendor.legal_name ?? '',
    bank_name: '',
    account_number: '',
    routing_code: '',
});

const submissionForm = useForm({
    submission_version: props.vendor.submission_version,
});

const resubmissionForm = useForm({
    submission_version: props.vendor.submission_version,
});

const isEditable = computed(() => props.vendor.can_edit);
const isPendingApproval = computed(
    () => props.vendor.status === 'pending_approval',
);
const isRejected = computed(() => props.vendor.status === 'rejected');
const isApproved = computed(() => props.vendor.status === 'approved');
const isSuspended = computed(() => props.vendor.status === 'suspended');

const businessComplete = computed(
    () =>
        !!props.vendor.legal_name &&
        !!props.vendor.display_name &&
        !!props.vendor.legal_entity_type,
);

const contactComplete = computed(
    () =>
        !!props.vendor.primary_contact_name &&
        !!props.vendor.primary_contact_email &&
        !!props.vendor.primary_contact_mobile_number,
);

const gstComplete = computed(
    () =>
        props.vendor.is_gst_registered !== null &&
        (props.vendor.is_gst_registered === false || !!props.vendor.gstin),
);

const requiredDocumentTypes = computed(() =>
    props.documentTypes
        .filter(
            (type) =>
                type.value !== 'gst_registration' ||
                props.vendor.is_gst_registered,
        )
        .map((type) => type.value),
);

const readyDocuments = computed(() => {
    const ready = new Set<string>();

    props.kycDocuments.forEach((document) => {
        if (
            document.status === 'active' &&
            (document.file_status === 'ready' || document.file_status === null)
        ) {
            ready.add(document.document_type);
        }
    });

    return ready;
});

const documentByType = computed(() => {
    const documents = new Map<string, VendorDocument>();

    props.kycDocuments.forEach((document) => {
        documents.set(document.document_type, document);
    });

    return documents;
});

const selectedDocument = computed(() =>
    documentByType.value.get(kycForm.document_type),
);
const selectedDocumentIsAttached = computed(
    () => selectedDocument.value !== undefined,
);
const hasProcessingDocuments = computed(() =>
    props.kycDocuments.some(
        (document) =>
            document.status === 'pending' ||
            document.file_status === 'uploaded' ||
            document.file_status === 'scanning',
    ),
);

const kycComplete = computed(() =>
    requiredDocumentTypes.value.every((type) => readyDocuments.value.has(type)),
);

const uploadedDocumentTypes = computed(() => {
    const uploaded = new Set<string>();

    props.kycDocuments.forEach((document) => {
        if (document.status !== 'rejected') {
            uploaded.add(document.document_type);
        }
    });

    locallyUploadedDocumentTypes.value.forEach((documentType) => {
        uploaded.add(documentType);
    });

    return uploaded;
});

const uploadedKycDocumentCount = computed(
    () =>
        requiredDocumentTypes.value.filter((type) =>
            uploadedDocumentTypes.value.has(type),
        ).length,
);

const kycUploadComplete = computed(
    () => uploadedKycDocumentCount.value === requiredDocumentTypes.value.length,
);

const activeBankAccount = computed(
    () =>
        props.bankAccounts.find((account) => account.status === 'active') ??
        null,
);
const bankComplete = computed(() => activeBankAccount.value !== null);
const canUploadDocument = computed(() => kycForm.document instanceof File);
const submissionSuccessMessage = ref<string | null>(null);
const submissionError = computed(() => {
    const errors = submissionForm.errors as Record<string, string | undefined>;

    return errors.submission;
});

let documentStatusPoll: number | undefined;

onMounted(() => {
    router.reload({ only: ['kycDocuments'] });

    documentStatusPoll = window.setInterval(() => {
        if (hasProcessingDocuments.value) {
            router.reload({ only: ['kycDocuments'] });
        }
    }, 5000);
});

onBeforeUnmount(() => {
    if (documentStatusPoll !== undefined) {
        window.clearInterval(documentStatusPoll);
    }
});

const reviewReady = computed(
    () =>
        businessComplete.value &&
        contactComplete.value &&
        gstComplete.value &&
        kycComplete.value &&
        bankComplete.value,
);

const onboardingSteps = computed(() => [
    {
        id: 'business',
        title: 'Business details',
        description: 'Legal identity, trading name, and entity structure.',
        complete: businessComplete.value,
    },
    {
        id: 'kyc',
        title: 'KYC upload',
        description: kycComplete.value
            ? 'Identity, registration, and GST evidence verified.'
            : kycUploadComplete.value
              ? 'Documents uploaded. Verification is in progress.'
              : `${uploadedKycDocumentCount.value}/${requiredDocumentTypes.value.length} required documents uploaded.`,
        complete: kycUploadComplete.value,
    },
    {
        id: 'bank',
        title: 'Bank details',
        description: 'Verified payout account used for settlements.',
        complete: bankComplete.value,
    },
    {
        id: 'review',
        title: 'Review and submit',
        description: 'Check the full draft before sending it for approval.',
        complete: reviewReady.value,
    },
]);

function submitBusinessDetails(): void {
    businessForm.put(props.routes.business_details, {
        preserveScroll: true,
    });
}

function submitPrimaryContact(): void {
    contactForm.put(props.routes.primary_contact, {
        preserveScroll: true,
    });
}

function submitGstDetails(): void {
    gstForm
        .transform((data) => ({
            ...data,
            is_gst_registered: data.is_gst_registered ? '1' : '0',
            gstin: data.is_gst_registered ? data.gstin : '',
        }))
        .put(props.routes.gst_details, {
            preserveScroll: true,
        });
}

function onDocumentSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const [file] = input.files ?? [];

    kycForm.document = file ?? null;
}

function uploadDocument(): void {
    const documentType = kycForm.document_type;

    kycForm.post(props.routes.kyc_documents, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            locallyUploadedDocumentTypes.value = new Set([
                ...locallyUploadedDocumentTypes.value,
                documentType,
            ]);
            kycForm.reset('document');

            if (documentInput.value) {
                documentInput.value.value = '';
            }

            router.reload({
                only: ['kycDocuments'],
            });
        },
    });
}

function documentUploadState(documentType: string): DocumentUploadState {
    const document = documentByType.value.get(documentType);

    if (!document) {
        return {
            description: 'Document still missing for the current version.',
            label: 'Missing',
            tone: 'missing',
        };
    }

    if (
        document.status === 'active' &&
        (document.file_status === 'ready' || document.file_status === null)
    ) {
        return {
            description: 'Evidence uploaded and ready for review.',
            label: 'Ready',
            tone: 'ready',
        };
    }

    if (
        document.status === 'rejected' ||
        document.file_status === 'failed' ||
        document.file_status === 'rejected'
    ) {
        return {
            description:
                'This file could not be verified. Contact support to replace it.',
            label: 'Needs attention',
            tone: 'attention',
        };
    }

    return {
        description: 'Uploaded securely. Verification is in progress.',
        label: 'Processing',
        tone: 'processing',
    };
}

function submitBankAccount(): void {
    bankForm.post(props.routes.bank_accounts, {
        preserveScroll: true,
    });
}

function submitForApproval(): void {
    submissionSuccessMessage.value = null;

    submissionForm.post(props.routes.submit, {
        preserveScroll: true,
        onSuccess: () => {
            const message = 'Registration submitted for approval.';

            submissionSuccessMessage.value = message;
            showToast({ type: 'success', message });
        },
    });
}

function prepareResubmission(): void {
    resubmissionForm.post(props.routes.prepare_resubmission, {
        preserveScroll: true,
    });
}

function prettify(value: string): string {
    return value.replaceAll('_', ' ');
}
</script>

<template>
    <Head title="Vendor onboarding" />

    <section
        class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.16),_transparent_38%),linear-gradient(180deg,#f7f7f2_0%,#eef2e5_100%)] px-4 py-6 text-slate-950 sm:px-6 sm:py-8"
    >
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-5">
            <div
                class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white/90 shadow-[0_30px_70px_-45px_rgba(15,23,42,0.55)] backdrop-blur"
            >
                <div
                    class="bg-[linear-gradient(135deg,rgba(15,23,42,0.98),rgba(30,41,59,0.94))] px-5 py-5 text-white sm:px-7 sm:py-7"
                >
                    <div
                        class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                    >
                        <div class="space-y-3">
                            <Link
                                :href="vendorRoutes.home()"
                                class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 text-sm font-medium text-white/90 transition hover:bg-white/15"
                            >
                                Vendor home
                            </Link>
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-[0.28em] text-emerald-300 uppercase"
                                >
                                    Vendor onboarding
                                </p>
                                <h1
                                    class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl"
                                >
                                    {{
                                        isPendingApproval
                                            ? 'Application under review'
                                            : isRejected
                                              ? 'Update and resubmit your registration'
                                              : isApproved
                                                ? 'Vendor registration approved'
                                                : isSuspended
                                                  ? 'Vendor access temporarily paused'
                                                  : 'Complete your registration draft'
                                    }}
                                </h1>
                                <p
                                    class="mt-3 max-w-3xl text-sm leading-6 text-slate-300 sm:text-base"
                                >
                                    {{
                                        isPendingApproval
                                            ? 'Your current submission is waiting for operations review. You can track the status here while the team verifies your documents.'
                                            : isRejected
                                              ? 'The review team requested updates. Fix the items below, refresh your evidence, and send a new submission version.'
                                              : isApproved
                                                ? 'Your vendor profile is approved and ready for the next operational setup steps.'
                                                : isSuspended
                                                  ? 'Operations has paused this vendor account. Contact support or your internal reviewer before continuing.'
                                                  : 'Finish the business, compliance, and payout steps below. Your draft saves as you move through the flow.'
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div
                                class="rounded-[1.5rem] border border-white/10 bg-white/8 px-4 py-3"
                            >
                                <p
                                    class="text-xs tracking-[0.22em] text-slate-300 uppercase"
                                >
                                    Status
                                </p>
                                <p
                                    class="mt-2 text-lg font-semibold capitalize"
                                >
                                    {{ prettify(vendor.status) }}
                                </p>
                            </div>
                            <div
                                class="rounded-[1.5rem] border border-white/10 bg-white/8 px-4 py-3"
                            >
                                <p
                                    class="text-xs tracking-[0.22em] text-slate-300 uppercase"
                                >
                                    Version
                                </p>
                                <p class="mt-2 text-lg font-semibold">
                                    v{{ vendor.submission_version }}
                                </p>
                            </div>
                            <div
                                class="rounded-[1.5rem] border border-white/10 bg-white/8 px-4 py-3"
                            >
                                <p
                                    class="text-xs tracking-[0.22em] text-slate-300 uppercase"
                                >
                                    Draft health
                                </p>
                                <p class="mt-2 text-lg font-semibold">
                                    {{
                                        onboardingSteps.filter(
                                            (step) => step.complete,
                                        ).length
                                    }}/4 ready
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="grid gap-5 px-5 py-5 lg:grid-cols-[0.9fr_1.1fr] lg:px-7 lg:py-6"
                >
                    <section
                        class="rounded-[1.75rem] border border-slate-200 bg-slate-50/80 p-4"
                    >
                        <div class="flex items-center gap-2">
                            <PhoneCall class="h-4 w-4 text-emerald-700" />
                            <h2 class="font-semibold">Owner contact</h2>
                        </div>
                        <dl class="mt-4 space-y-3 text-sm text-slate-700">
                            <div>
                                <dt
                                    class="text-xs tracking-[0.22em] text-slate-500 uppercase"
                                >
                                    Name
                                </dt>
                                <dd class="mt-1 font-medium">
                                    {{ owner.name ?? 'Pending' }}
                                </dd>
                            </div>
                            <div>
                                <dt
                                    class="text-xs tracking-[0.22em] text-slate-500 uppercase"
                                >
                                    Mobile
                                </dt>
                                <dd class="mt-1 font-medium">
                                    {{ owner.mobile_number ?? 'Pending' }}
                                </dd>
                            </div>
                            <div>
                                <dt
                                    class="text-xs tracking-[0.22em] text-slate-500 uppercase"
                                >
                                    Email
                                </dt>
                                <dd class="mt-1 font-medium">
                                    {{ owner.email ?? 'Pending' }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section
                        class="rounded-[1.75rem] border border-slate-200 bg-white p-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-[0.24em] text-slate-500 uppercase"
                                >
                                    Flow progress
                                </p>
                                <h2 class="mt-2 text-xl font-semibold">
                                    Mobile-first onboarding checklist
                                </h2>
                            </div>
                            <span
                                class="rounded-full bg-emerald-100 px-3 py-2 text-xs font-semibold tracking-[0.2em] text-emerald-900 uppercase"
                            >
                                Submission v{{ vendor.submission_version }}
                            </span>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <article
                                v-for="step in onboardingSteps"
                                :key="step.id"
                                class="rounded-[1.5rem] border px-4 py-4"
                                :class="
                                    step.complete
                                        ? 'border-emerald-200 bg-emerald-50'
                                        : 'border-slate-200 bg-slate-50'
                                "
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <h3 class="font-semibold">
                                            {{ step.title }}
                                        </h3>
                                        <p
                                            class="mt-2 text-sm leading-6 text-slate-600"
                                        >
                                            {{ step.description }}
                                        </p>
                                    </div>
                                    <BadgeCheck
                                        v-if="step.complete"
                                        class="h-5 w-5 shrink-0 text-emerald-600"
                                    />
                                    <Clock3
                                        v-else
                                        class="h-5 w-5 shrink-0 text-slate-400"
                                    />
                                </div>
                            </article>
                        </div>
                    </section>
                </div>
            </div>

            <section
                v-if="isRejected && rejection"
                class="rounded-[2rem] border border-amber-300 bg-amber-50 px-5 py-5 shadow-sm"
            >
                <div class="flex items-start gap-3">
                    <AlertTriangle
                        class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                    />
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg font-semibold text-amber-950">
                            Reviewer updates required
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-amber-900">
                            {{ rejection.reason_message }}
                        </p>
                        <Button
                            class="mt-4 rounded-full bg-amber-900 text-white hover:bg-amber-800"
                            type="button"
                            :disabled="resubmissionForm.processing"
                            @click="prepareResubmission"
                        >
                            <RefreshCcw class="h-4 w-4" />
                            Reopen registration for v{{
                                vendor.submission_version + 1
                            }}
                        </Button>
                    </div>
                </div>
            </section>

            <section
                v-if="isPendingApproval"
                class="rounded-[2rem] border border-sky-200 bg-sky-50 px-5 py-5 shadow-sm"
            >
                <div class="flex items-start gap-3">
                    <ShieldAlert class="mt-0.5 h-5 w-5 shrink-0 text-sky-700" />
                    <div>
                        <h2 class="text-lg font-semibold text-sky-950">
                            Pending approval
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-sky-900">
                            Your draft is locked while review is in progress. If
                            operations needs anything else, the status here will
                            change to rejected with clear resubmission guidance.
                        </p>
                        <p
                            v-if="submissionSuccessMessage"
                            class="mt-4 rounded-2xl border border-sky-200 bg-white/80 px-4 py-3 text-sm font-semibold text-sky-950"
                        >
                            {{ submissionSuccessMessage }}
                        </p>
                    </div>
                </div>
            </section>

            <section
                v-if="isApproved"
                class="rounded-[2rem] border border-emerald-200 bg-emerald-50 px-5 py-5 shadow-sm"
            >
                <div class="flex items-start gap-3">
                    <BadgeCheck
                        class="mt-0.5 h-5 w-5 shrink-0 text-emerald-700"
                    />
                    <div>
                        <h2 class="text-lg font-semibold text-emerald-950">
                            Registration approved
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-emerald-900">
                            Business verification is complete. Vendor locations,
                            turf setup, and operational controls can continue
                            from here.
                        </p>
                    </div>
                </div>
            </section>

            <section
                v-if="isSuspended"
                class="rounded-[2rem] border border-rose-200 bg-rose-50 px-5 py-5 shadow-sm"
            >
                <div class="flex items-start gap-3">
                    <ShieldAlert
                        class="mt-0.5 h-5 w-5 shrink-0 text-rose-700"
                    />
                    <div>
                        <h2 class="text-lg font-semibold text-rose-950">
                            Vendor access paused
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-rose-900">
                            This vendor account is currently suspended. Internal
                            suspension notes are intentionally hidden from this
                            surface.
                        </p>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 xl:grid-cols-[1.05fr_0.95fr]">
                <div class="space-y-5">
                    <form
                        class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm"
                        @submit.prevent="submitBusinessDetails"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-2xl bg-emerald-100 p-3 text-emerald-800"
                            >
                                <Building2 class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">
                                    Business details
                                </h2>
                                <p class="text-sm text-slate-600">
                                    Capture the legal entity your vendor profile
                                    operates under.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4">
                            <div class="grid gap-2">
                                <Label for="legal_name">Legal name</Label>
                                <Input
                                    id="legal_name"
                                    v-model="businessForm.legal_name"
                                    :disabled="!isEditable"
                                    class="h-12 rounded-2xl"
                                    placeholder="Acme Sports Private Limited"
                                />
                                <InputError
                                    :message="businessForm.errors.legal_name"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="display_name">Display name</Label>
                                <Input
                                    id="display_name"
                                    v-model="businessForm.display_name"
                                    :disabled="!isEditable"
                                    class="h-12 rounded-2xl"
                                    placeholder="Acme Sports Arena"
                                />
                                <InputError
                                    :message="businessForm.errors.display_name"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="legal_entity_type"
                                    >Entity type</Label
                                >
                                <Input
                                    id="legal_entity_type"
                                    v-model="businessForm.legal_entity_type"
                                    :disabled="!isEditable"
                                    class="h-12 rounded-2xl"
                                    placeholder="private_limited_company"
                                />
                                <InputError
                                    :message="
                                        businessForm.errors.legal_entity_type
                                    "
                                />
                            </div>
                        </div>

                        <Button
                            v-if="isEditable"
                            class="mt-5 rounded-full"
                            type="submit"
                            :disabled="businessForm.processing"
                        >
                            Save business details
                        </Button>
                    </form>

                    <form
                        class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm"
                        @submit.prevent="submitPrimaryContact"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-2xl bg-sky-100 p-3 text-sky-800"
                            >
                                <PhoneCall class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">
                                    Primary contact
                                </h2>
                                <p class="text-sm text-slate-600">
                                    This person is the day-to-day operations
                                    contact for review and payouts.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4">
                            <div class="grid gap-2">
                                <Label for="primary_contact_name"
                                    >Contact name</Label
                                >
                                <Input
                                    id="primary_contact_name"
                                    v-model="contactForm.primary_contact_name"
                                    :disabled="!isEditable"
                                    class="h-12 rounded-2xl"
                                />
                                <InputError
                                    :message="
                                        contactForm.errors.primary_contact_name
                                    "
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="primary_contact_email">Email</Label>
                                <Input
                                    id="primary_contact_email"
                                    v-model="contactForm.primary_contact_email"
                                    :disabled="!isEditable"
                                    type="email"
                                    class="h-12 rounded-2xl"
                                />
                                <InputError
                                    :message="
                                        contactForm.errors.primary_contact_email
                                    "
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="primary_contact_mobile_number"
                                    >Mobile number</Label
                                >
                                <Input
                                    id="primary_contact_mobile_number"
                                    v-model="
                                        contactForm.primary_contact_mobile_number
                                    "
                                    :disabled="!isEditable"
                                    class="h-12 rounded-2xl"
                                    placeholder="+919900001111"
                                />
                                <InputError
                                    :message="
                                        contactForm.errors
                                            .primary_contact_mobile_number
                                    "
                                />
                            </div>
                        </div>

                        <Button
                            v-if="isEditable"
                            class="mt-5 rounded-full"
                            type="submit"
                            :disabled="contactForm.processing"
                        >
                            Save contact details
                        </Button>
                    </form>

                    <form
                        class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm"
                        @submit.prevent="submitGstDetails"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-2xl bg-amber-100 p-3 text-amber-800"
                            >
                                <FileCheck2 class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">
                                    Tax and GST
                                </h2>
                                <p class="text-sm text-slate-600">
                                    Declare whether the business is
                                    GST-registered and add the GSTIN when
                                    required.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-4">
                            <label
                                class="flex items-center gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-medium text-slate-700"
                            >
                                <input
                                    v-model="gstForm.is_gst_registered"
                                    :disabled="!isEditable"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-emerald-600"
                                />
                                This vendor is GST registered
                            </label>

                            <div class="grid gap-2">
                                <Label for="gstin">GSTIN</Label>
                                <Input
                                    id="gstin"
                                    v-model="gstForm.gstin"
                                    :disabled="
                                        !isEditable ||
                                        !gstForm.is_gst_registered
                                    "
                                    class="h-12 rounded-2xl uppercase"
                                    placeholder="27ABCDE1234F1Z5"
                                />
                                <InputError :message="gstForm.errors.gstin" />
                            </div>
                        </div>

                        <Button
                            v-if="isEditable"
                            class="mt-5 rounded-full"
                            type="submit"
                            :disabled="gstForm.processing"
                        >
                            Save GST details
                        </Button>
                    </form>
                </div>

                <div class="space-y-5">
                    <form
                        class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm"
                        @submit.prevent="uploadDocument"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-2xl bg-violet-100 p-3 text-violet-800"
                            >
                                <Upload class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">
                                    KYC upload
                                </h2>
                                <p class="text-sm text-slate-600">
                                    Upload the current evidence package for this
                                    submission version.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4">
                            <div class="grid gap-2">
                                <Label for="document_type">Document type</Label>
                                <select
                                    id="document_type"
                                    v-model="kycForm.document_type"
                                    :disabled="!isEditable"
                                    class="h-12 rounded-2xl border border-input bg-background px-4 text-sm"
                                >
                                    <option
                                        v-for="option in documentTypes"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <InputError
                                    :message="kycForm.errors.document_type"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="document">File</Label>
                                <Input
                                    id="document"
                                    ref="documentInput"
                                    :disabled="!isEditable"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    class="h-12 rounded-2xl file:mr-4 file:border-0 file:bg-transparent file:font-medium"
                                    @change="onDocumentSelected"
                                />
                                <InputError
                                    :message="kycForm.errors.document"
                                />
                            </div>
                        </div>

                        <p
                            v-if="selectedDocumentIsAttached"
                            class="mt-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm leading-6 text-sky-950"
                        >
                            {{
                                documentUploadState(kycForm.document_type)
                                    .description
                            }}
                            Choose another document type to upload additional
                            evidence.
                        </p>

                        <Button
                            v-if="isEditable"
                            class="mt-5 rounded-full"
                            type="submit"
                            :disabled="kycForm.processing || !canUploadDocument"
                        >
                            Upload KYC document
                        </Button>

                        <div class="mt-6 space-y-3">
                            <div
                                v-for="option in documentTypes"
                                :key="option.value"
                                class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-3"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <div>
                                        <p class="font-medium">
                                            {{ option.label }}
                                        </p>
                                        <p class="mt-1 text-sm text-slate-600">
                                            {{
                                                documentUploadState(
                                                    option.value,
                                                ).description
                                            }}
                                        </p>
                                    </div>
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold uppercase"
                                        :class="
                                            documentUploadState(option.value)
                                                .tone === 'ready'
                                                ? 'bg-emerald-100 text-emerald-900'
                                                : documentUploadState(
                                                        option.value,
                                                    ).tone === 'processing'
                                                  ? 'bg-sky-100 text-sky-900'
                                                  : documentUploadState(
                                                          option.value,
                                                      ).tone === 'attention'
                                                    ? 'bg-rose-100 text-rose-900'
                                                    : 'bg-slate-200 text-slate-700'
                                        "
                                    >
                                        {{
                                            documentUploadState(option.value)
                                                .label
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </form>

                    <form
                        class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm"
                        @submit.prevent="submitBankAccount"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-2xl bg-rose-100 p-3 text-rose-800"
                            >
                                <Landmark class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">
                                    Bank details
                                </h2>
                                <p class="text-sm text-slate-600">
                                    Add the payout account that should receive
                                    vendor settlements.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4">
                            <div class="grid gap-2">
                                <Label for="account_holder_name"
                                    >Account holder name</Label
                                >
                                <Input
                                    id="account_holder_name"
                                    v-model="bankForm.account_holder_name"
                                    :disabled="!isEditable"
                                    class="h-12 rounded-2xl"
                                />
                                <InputError
                                    :message="
                                        bankForm.errors.account_holder_name
                                    "
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="bank_name">Bank name</Label>
                                <Input
                                    id="bank_name"
                                    v-model="bankForm.bank_name"
                                    :disabled="!isEditable"
                                    class="h-12 rounded-2xl"
                                />
                                <InputError
                                    :message="bankForm.errors.bank_name"
                                />
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2 sm:items-end">
                                <div class="grid gap-2">
                                    <Label for="account_number"
                                        >Account number</Label
                                    >
                                    <Input
                                        id="account_number"
                                        v-model="bankForm.account_number"
                                        :disabled="!isEditable"
                                        class="h-12 rounded-2xl"
                                        inputmode="numeric"
                                    />
                                    <InputError
                                        :message="
                                            bankForm.errors.account_number
                                        "
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="routing_code"
                                        >IFSC / routing code</Label
                                    >
                                    <Input
                                        id="routing_code"
                                        v-model="bankForm.routing_code"
                                        :disabled="!isEditable"
                                        class="h-12 rounded-2xl uppercase"
                                    />
                                    <InputError
                                        :message="bankForm.errors.routing_code"
                                    />
                                </div>
                            </div>
                        </div>

                        <Button
                            v-if="isEditable"
                            class="mt-5 rounded-full"
                            type="submit"
                            :disabled="bankForm.processing"
                        >
                            Save bank account
                        </Button>

                        <div
                            v-if="activeBankAccount"
                            class="mt-6 rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-4 py-4"
                        >
                            <p class="text-sm font-semibold text-emerald-950">
                                Active payout account
                            </p>
                            <p class="mt-2 text-sm leading-6 text-emerald-900">
                                {{ activeBankAccount.bank_name }} · ••••
                                {{ activeBankAccount.account_number_last_four
                                }}<br />
                                {{ activeBankAccount.country_code }} ·
                                {{ activeBankAccount.currency }}
                            </p>
                        </div>
                    </form>

                    <section
                        class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-2xl bg-slate-900 p-3 text-white"
                            >
                                <ArrowRight class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">
                                    Review and submit
                                </h2>
                                <p class="text-sm text-slate-600">
                                    Confirm what reviewers will see before you
                                    send this version for approval.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4">
                            <article
                                class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4"
                            >
                                <h3 class="font-semibold">Business summary</h3>
                                <p
                                    class="mt-2 text-sm leading-6 text-slate-700"
                                >
                                    {{
                                        vendor.legal_name ??
                                        'Legal name missing'
                                    }}<br />
                                    {{
                                        vendor.display_name ??
                                        'Display name missing'
                                    }}<br />
                                    {{
                                        vendor.legal_entity_type ??
                                        'Entity type missing'
                                    }}
                                </p>
                            </article>

                            <article
                                class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4"
                            >
                                <h3 class="font-semibold">Primary contact</h3>
                                <p
                                    class="mt-2 text-sm leading-6 text-slate-700"
                                >
                                    {{
                                        vendor.primary_contact_name ??
                                        'Contact missing'
                                    }}<br />
                                    {{
                                        vendor.primary_contact_email ??
                                        'Email missing'
                                    }}<br />
                                    {{
                                        vendor.primary_contact_mobile_number ??
                                        'Mobile missing'
                                    }}
                                </p>
                            </article>

                            <article
                                class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4"
                            >
                                <h3 class="font-semibold">
                                    Compliance package
                                </h3>
                                <ul
                                    class="mt-3 space-y-2 text-sm text-slate-700"
                                >
                                    <li>
                                        GST:
                                        {{
                                            vendor.is_gst_registered === null
                                                ? 'Pending'
                                                : vendor.is_gst_registered
                                                  ? vendor.gstin
                                                  : 'Not registered'
                                        }}
                                    </li>
                                    <li>
                                        KYC documents:
                                        {{ uploadedKycDocumentCount }}/{{
                                            requiredDocumentTypes.length
                                        }}
                                        uploaded, {{ readyDocuments.size }}/{{
                                            requiredDocumentTypes.length
                                        }}
                                        verified
                                    </li>
                                    <li>
                                        Bank account:
                                        {{
                                            activeBankAccount
                                                ? `${activeBankAccount.bank_name} ending ${activeBankAccount.account_number_last_four}`
                                                : 'Missing'
                                        }}
                                    </li>
                                </ul>
                            </article>
                        </div>

                        <div
                            v-if="!reviewReady"
                            class="mt-5 rounded-[1.5rem] border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-950"
                        >
                            Complete every section above before submitting this
                            version for approval.
                        </div>

                        <InputError class="mt-4" :message="submissionError" />
                        <InputError
                            class="mt-2"
                            :message="submissionForm.errors.submission_version"
                        />

                        <Button
                            v-if="isEditable"
                            class="mt-5 w-full rounded-[1.35rem] bg-slate-950 py-6 text-base hover:bg-slate-800"
                            type="button"
                            :disabled="
                                submissionForm.processing || !reviewReady
                            "
                            @click="submitForApproval"
                        >
                            Submit for approval
                        </Button>
                    </section>
                </div>
            </div>
        </div>
    </section>
</template>
