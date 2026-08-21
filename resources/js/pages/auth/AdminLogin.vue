<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import FormFeedback from '@/components/feedback/FormFeedback.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

defineOptions({
    layout: {
        title: 'Admin sign in',
        description:
            'Use your staff email and password to access the admin workspace.',
    },
});

defineProps<{
    canResetPassword: boolean;
    status?: string;
}>();
</script>

<template>
    <Head title="Admin login" />

    <div class="space-y-6">
        <div class="rounded-3xl border border-sky-100 bg-sky-50/80 p-4">
            <p
                class="text-[0.72rem] font-semibold tracking-[0.24em] text-sky-800 uppercase"
            >
                Admin portal
            </p>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                This sign-in is reserved for platform operators. Customer OTP
                access stays available on
                <TextLink href="/login" class="font-medium"
                    >the standard login page</TextLink
                >.
            </p>
        </div>

        <Form
            v-bind="store.form()"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-6">
                <div v-if="status">
                    <FormFeedback :message="status" variant="success" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Work email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="admin@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-3">
                        <Label for="password">Password</Label>
                        <TextLink
                            v-if="canResetPassword"
                            href="/forgot-password"
                            class="text-xs"
                        >
                            Forgot password?
                        </TextLink>
                    </div>

                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <Label
                    for="remember"
                    class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600"
                >
                    <input
                        id="remember"
                        type="checkbox"
                        name="remember"
                        value="1"
                        class="h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-slate-400"
                    />
                    Keep this admin session signed in on this device
                </Label>

                <Button
                    type="submit"
                    class="w-full bg-slate-950 text-white hover:bg-slate-800"
                    :disabled="processing"
                    data-test="admin-login-button"
                >
                    <Spinner v-if="processing" />
                    Sign in to admin
                </Button>
            </div>
        </Form>
    </div>
</template>
