<script setup lang="ts">
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
}>();
</script>

<template>
    <section class="min-h-screen bg-slate-950 px-6 py-10 text-slate-50">
        <div class="mx-auto flex max-w-md flex-col gap-6">
            <div class="space-y-2">
                <p class="text-sm uppercase tracking-[0.3em] text-emerald-300">Vendor onboarding</p>
                <h1 class="text-3xl font-semibold">Registration draft started</h1>
                <p class="text-sm leading-6 text-slate-300">
                    Your vendor workspace is ready. We&apos;ll layer in business, KYC, and bank steps next.
                </p>
            </div>

            <dl class="grid gap-4 rounded-3xl border border-white/10 bg-white/5 p-5">
                <div>
                    <dt class="text-xs uppercase tracking-[0.2em] text-slate-400">Vendor ID</dt>
                    <dd class="mt-1 text-lg font-medium">{{ vendor.id }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.2em] text-slate-400">Status</dt>
                    <dd class="mt-1 text-lg font-medium capitalize">{{ vendor.status.replace('_', ' ') }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.2em] text-slate-400">Submission version</dt>
                    <dd class="mt-1 text-lg font-medium">{{ vendor.submission_version }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.2em] text-slate-400">Owner contact</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-200">
                        {{ owner.name ?? 'Name pending' }}<br>
                        {{ owner.mobile_number ?? 'Mobile pending' }}<br>
                        {{ owner.email ?? 'Email pending' }}
                    </dd>
                </div>
            </dl>
        </div>
    </section>
</template>
