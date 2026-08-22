<script setup lang="ts">
import { router } from '@inertiajs/vue3';
defineProps<{
    vendor: {
        id: number;
        status: string;
        submission_version: number;
    };
    owner: {
        name: string | null;
        mobile_number: string | null;
        email: string | null;
    };
    rejection: {
        reason_code: string | null;
        reason_message: string | null;
        transitioned_at: string;
    } | null;
    routes: {
        prepare_resubmission: string;
    };
}>();

function prepareResubmission(): void {
    router.post(props.routes.prepare_resubmission, {
        submission_version: props.vendor.submission_version,
    });
}
</script>

<template>
    <section class="min-h-screen bg-slate-950 px-6 py-10 text-slate-50">
        <div class="mx-auto flex max-w-md flex-col gap-6">
            <div class="space-y-2">
                <p class="text-sm tracking-[0.3em] text-emerald-300 uppercase">
                    Vendor onboarding
                </p>
                <h1 class="text-3xl font-semibold">
                    Registration draft started
                </h1>
                <p class="text-sm leading-6 text-slate-300">
                    Your vendor workspace is ready. We&apos;ll layer in
                    business, KYC, and bank steps next.
                </p>
            </div>

            <dl
                class="grid gap-4 rounded-3xl border border-white/10 bg-white/5 p-5"
            >
                <div>
                    <dt
                        class="text-xs tracking-[0.2em] text-slate-400 uppercase"
                    >
                        Vendor ID
                    </dt>
                    <dd class="mt-1 text-lg font-medium">{{ vendor.id }}</dd>
                </div>
                <div>
                    <dt
                        class="text-xs tracking-[0.2em] text-slate-400 uppercase"
                    >
                        Status
                    </dt>
                    <dd class="mt-1 text-lg font-medium capitalize">
                        {{ vendor.status.replace('_', ' ') }}
                    </dd>
                </div>
                <div>
                    <dt
                        class="text-xs tracking-[0.2em] text-slate-400 uppercase"
                    >
                        Submission version
                    </dt>
                    <dd class="mt-1 text-lg font-medium">
                        {{ vendor.submission_version }}
                    </dd>
                </div>
                <div>
                    <dt
                        class="text-xs tracking-[0.2em] text-slate-400 uppercase"
                    >
                        Owner contact
                    </dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-200">
                        {{ owner.name ?? 'Name pending' }}<br />
                        {{ owner.mobile_number ?? 'Mobile pending' }}<br />
                        {{ owner.email ?? 'Email pending' }}
                    </dd>
                </div>
            </dl>

            <section
                v-if="vendor.status === 'rejected' && rejection"
                class="rounded-3xl border border-amber-300/30 bg-amber-300/10 p-5 text-sm text-amber-50"
            >
                <p class="font-semibold">
                    Updates are required before resubmission
                </p>
                <p class="mt-2 leading-6">{{ rejection.reason_message }}</p>
                <button
                    class="mt-4 rounded-xl bg-amber-200 px-4 py-2 font-medium text-slate-950"
                    type="button"
                    @click="prepareResubmission"
                >
                    Reopen registration
                </button>
            </section>
        </div>
    </section>
</template>
