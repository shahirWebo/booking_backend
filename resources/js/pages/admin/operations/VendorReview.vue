<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    Landmark,
    ShieldCheck,
    XCircle,
} from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

type VendorReview = {
    id: number;
    submission_version: number;
    submitted_at: string;
    business: {
        legal_name: string;
        display_name: string;
        legal_entity_type: string;
    };
    primary_contact: {
        name: string;
        email: string;
        mobile_number: string;
    };
    gst: {
        is_registered: boolean;
        gstin: string | null;
    };
    bank_account: {
        bank_name: string;
        account_number_last_four: string;
        country_code: string;
        currency: string;
    } | null;
    documents: Array<{
        id: number;
        document_type: string;
        status: string;
    }>;
};

const props = defineProps<{
    vendor: VendorReview;
    routes: {
        index: string;
        approve: string;
        reject: string;
    };
}>();

const rejectionReasonCode = ref('document_verification_required');
const rejectionReasonMessage = ref('');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Vendor reviews', href: '/admin/operations/vendors' },
        ],
    },
});

function approve(): void {
    if (!window.confirm('Approve this vendor application?')) {
        return;
    }

    router.post(props.routes.approve, {
        submission_version: props.vendor.submission_version,
    });
}

function reject(): void {
    if (
        !window.confirm(
            'Reject this application and return it to the vendor for correction?',
        )
    ) {
        return;
    }

    router.post(props.routes.reject, {
        submission_version: props.vendor.submission_version,
        reason_code: rejectionReasonCode.value,
        reason_message: rejectionReasonMessage.value,
    });
}
</script>

<template>
    <Head title="Review vendor" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <section
            class="rounded-3xl border border-sidebar-border/70 bg-sidebar-accent/40 p-5 dark:border-sidebar-border"
        >
            <Link
                :href="routes.index"
                class="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition hover:text-foreground"
            >
                <ArrowLeft class="h-4 w-4" />
                Back to vendor reviews
            </Link>
            <div
                class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.24em] text-sidebar-foreground/60 uppercase"
                    >
                        Vendor application
                    </p>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight">
                        {{ vendor.business.display_name }}
                    </h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Submission v{{ vendor.submission_version }} ·
                        {{ vendor.submitted_at }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" @click="reject">
                        <XCircle class="h-4 w-4" />
                        Reject application
                    </Button>
                    <Button @click="approve">
                        <BadgeCheck class="h-4 w-4" />
                        Approve vendor
                    </Button>
                </div>
            </div>
        </section>

        <div class="grid gap-4 xl:grid-cols-2">
            <section
                class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div class="flex items-center gap-2">
                    <ShieldCheck class="h-5 w-5 text-muted-foreground" />
                    <h2 class="font-semibold">Business and contact</h2>
                </div>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="text-muted-foreground">Legal name</dt>
                        <dd class="mt-1 font-medium">
                            {{ vendor.business.legal_name }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Entity type</dt>
                        <dd class="mt-1 font-medium">
                            {{ vendor.business.legal_entity_type }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Primary contact</dt>
                        <dd class="mt-1 font-medium">
                            {{ vendor.primary_contact.name }}<br />{{
                                vendor.primary_contact.email
                            }}<br />{{ vendor.primary_contact.mobile_number }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">GST</dt>
                        <dd class="mt-1 font-medium">
                            {{
                                vendor.gst.is_registered
                                    ? vendor.gst.gstin
                                    : 'Not registered'
                            }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section
                class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
            >
                <div class="flex items-center gap-2">
                    <Landmark class="h-5 w-5 text-muted-foreground" />
                    <h2 class="font-semibold">Evidence</h2>
                </div>
                <dl v-if="vendor.bank_account" class="mt-5 text-sm">
                    <dt class="text-muted-foreground">Payout account</dt>
                    <dd class="mt-1 font-medium">
                        {{ vendor.bank_account.bank_name }} · ••••
                        {{ vendor.bank_account.account_number_last_four
                        }}<br />{{ vendor.bank_account.country_code }} ·
                        {{ vendor.bank_account.currency }}
                    </dd>
                </dl>
                <p v-else class="mt-5 text-sm text-destructive">
                    Bank account evidence is unavailable.
                </p>
                <div class="mt-6">
                    <p class="text-sm text-muted-foreground">KYC documents</p>
                    <ul class="mt-2 space-y-2 text-sm">
                        <li
                            v-for="document in vendor.documents"
                            :key="document.id"
                            class="rounded-xl bg-muted px-3 py-2 font-medium"
                        >
                            {{ document.document_type.replace('_', ' ') }} ·
                            {{ document.status }}
                        </li>
                    </ul>
                </div>
            </section>
        </div>

        <section
            class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
        >
            <h2 class="font-semibold">Rejection guidance</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                This owner-facing message explains what must be corrected. Do
                not include internal investigations, risk signals, or policy
                notes.
            </p>
            <div
                class="mt-4 grid gap-4 sm:grid-cols-[minmax(0,0.35fr)_minmax(0,0.65fr)]"
            >
                <label class="grid gap-2 text-sm font-medium">
                    Reason category
                    <select
                        v-model="rejectionReasonCode"
                        class="rounded-xl border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="business_information_mismatch">
                            Business information mismatch
                        </option>
                        <option value="document_verification_required">
                            Document verification required
                        </option>
                        <option value="incomplete_submission">
                            Incomplete submission
                        </option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label class="grid gap-2 text-sm font-medium">
                    Safe message
                    <textarea
                        v-model="rejectionReasonMessage"
                        rows="3"
                        class="rounded-xl border border-input bg-background px-3 py-2 text-sm"
                        placeholder="Describe the correction required for resubmission."
                    />
                </label>
            </div>
        </section>
    </div>
</template>
