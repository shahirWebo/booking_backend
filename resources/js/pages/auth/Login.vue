<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import FormFeedback from '@/components/feedback/FormFeedback.vue';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    submitInertiaForm,
    useFormSubmission,
} from '@/composables/useFormSubmission';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Log in to your account',
        description: 'Enter your email and password below to log in',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const { errorFor, generalError, hasRetry, processing, retry, submit } =
    useFormSubmission();

const submitLoginForm = () =>
    submit(
        () =>
            submitInertiaForm(form, store(), {
                resetOnSuccess: ['password'],
            }),
        {
            errorMessage: 'Unable to log in right now.',
        },
    );
</script>

<template>
    <Head title="Log in" />

    <div v-if="status" class="mb-4">
        <FormFeedback :message="status" variant="success" />
    </div>

    <PasskeyVerify />

    <form class="flex flex-col gap-6" @submit.prevent="submitLoginForm">
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="email@example.com"
                    v-model="form.email"
                />
                <InputError :message="errorFor('email') ?? form.errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Password</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        Forgot your password?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Password"
                    v-model="form.password"
                />
                <InputError
                    :message="errorFor('password') ?? form.errors.password"
                />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox
                        id="remember"
                        name="remember"
                        :tabindex="3"
                        v-model="form.remember"
                    />
                    <span>Remember me</span>
                </Label>
            </div>

            <FormFeedback
                v-if="generalError"
                :message="generalError"
                variant="error"
            >
                <Button
                    v-if="hasRetry"
                    type="button"
                    variant="link"
                    class="h-auto p-0 text-red-700"
                    @click="retry"
                >
                    Try the last submission again
                </Button>
            </FormFeedback>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Log in
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Don't have an account?
            <TextLink :href="register()" :tabindex="5">Sign up</TextLink>
        </div>
    </form>
</template>
