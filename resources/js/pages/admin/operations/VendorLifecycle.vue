<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, RotateCcw, ShieldAlert } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

const props = defineProps<{
    vendor: {
        id: number;
        display_name: string | null;
        legal_name: string | null;
        status: string;
        submission_version: number;
        last_transitioned_at: string | null;
    };
    permissions: {
        can_suspend: boolean;
        can_reactivate: boolean;
    };
    routes: {
        index: string;
        suspend: string;
        reactivate: string;
    };
}>();

const suspensionReasonCode = ref('compliance_review');
const suspensionReasonMessage = ref('');
const reactivationReasonMessage = ref('');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            {
                title: 'Vendor lifecycle',
                href: '/admin/operations/vendors/lifecycle',
            },
        ],
    },
});

function suspend(): void {
    if (!window.confirm('Suspend this vendor and stop new commerce access?')) {
        return;
    }

    router.post(props.routes.suspend, {
        submission_version: props.vendor.submission_version,
        reason_code: suspensionReasonCode.value,
        reason_message: suspensionReasonMessage.value,
    });
}

function reactivate(): void {
    if (
        !window.confirm(
            'Reactivate this vendor after confirming the review is complete?',
        )
    ) {
        return;
    }

    router.post(props.routes.reactivate, {
        submission_version: props.vendor.submission_version,
        reason_message: reactivationReasonMessage.value,
    });
}
</script>

<template>
    <Head title="Vendor lifecycle" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <section
            class="rounded-3xl border border-sidebar-border/70 bg-sidebar-accent/40 p-5 dark:border-sidebar-border"
        >
            <Link
                :href="routes.index"
                class="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition hover:text-foreground"
                ><ArrowLeft class="h-4 w-4" /> Back to vendor lifecycle</Link
            >
            <h1 class="mt-5 text-2xl font-semibold tracking-tight">
                {{
                    vendor.display_name ??
                    vendor.legal_name ??
                    `Vendor #${vendor.id}`
                }}
            </h1>
            <p class="mt-2 text-sm text-muted-foreground capitalize">
                Current status: {{ vendor.status.replace('_', ' ') }}
            </p>
        </section>

        <section
            v-if="vendor.status === 'approved' || vendor.status === 'inactive'"
            class="rounded-3xl border border-amber-500/30 bg-amber-500/5 p-5"
        >
            <div class="flex items-center gap-2">
                <ShieldAlert class="h-5 w-5 text-amber-600" />
                <h2 class="font-semibold">Suspend vendor</h2>
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
                This stops new commerce access. Existing bookings and financial
                records are retained.
            </p>
            <div
                v-if="permissions.can_suspend"
                class="mt-4 grid gap-4 sm:grid-cols-2"
            >
                <label class="grid gap-2 text-sm font-medium"
                    >Reason category<select
                        v-model="suspensionReasonCode"
                        class="rounded-xl border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="compliance_review">
                            Compliance review
                        </option>
                        <option value="operational_risk">
                            Operational risk
                        </option>
                        <option value="policy_violation">
                            Policy violation
                        </option>
                        <option value="suspected_fraud">Suspected fraud</option>
                    </select></label
                >
                <label class="grid gap-2 text-sm font-medium"
                    >Internal review note<textarea
                        v-model="suspensionReasonMessage"
                        rows="3"
                        class="rounded-xl border border-input bg-background px-3 py-2 text-sm"
                        placeholder="Record the authorized operational reason."
                    />
                </label>
            </div>
            <Button
                v-if="permissions.can_suspend"
                class="mt-4"
                variant="destructive"
                @click="suspend"
                ><ShieldAlert class="h-4 w-4" /> Suspend vendor</Button
            >
        </section>

        <section
            v-if="vendor.status === 'suspended'"
            class="rounded-3xl border border-emerald-500/30 bg-emerald-500/5 p-5"
        >
            <div class="flex items-center gap-2">
                <RotateCcw class="h-5 w-5 text-emerald-600" />
                <h2 class="font-semibold">Reactivate vendor</h2>
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
                Confirm the suspension review has cleared and an active vendor
                owner remains before restoring eligibility.
            </p>
            <label
                v-if="permissions.can_reactivate"
                class="mt-4 grid gap-2 text-sm font-medium"
                >Internal review note<textarea
                    v-model="reactivationReasonMessage"
                    rows="3"
                    class="rounded-xl border border-input bg-background px-3 py-2 text-sm"
                    placeholder="Record why the vendor is eligible again."
                />
            </label>
            <Button
                v-if="permissions.can_reactivate"
                class="mt-4"
                @click="reactivate"
                ><RotateCcw class="h-4 w-4" /> Reactivate vendor</Button
            >
        </section>
    </div>
</template>
