<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import FormFeedback from '@/components/feedback/FormFeedback.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    submitInertiaForm,
    useFormSubmission,
} from '@/composables/useFormSubmission';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const { errorFor, generalError, hasRetry, processing, retry, submit } =
    useFormSubmission();

const submitRegisterForm = () =>
    submit(
        () =>
            submitInertiaForm(form, store(), {
                resetOnSuccess: ['password', 'password_confirmation'],
            }),
        {
            errorMessage: 'Unable to create your account right now.',
        },
    );
</script>

<template>
    <Head title="Register" />

    <form class="flex flex-col gap-6" @submit.prevent="submitRegisterForm">
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Full name"
                    v-model="form.name"
                />
                <InputError :message="errorFor('name') ?? form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                    v-model="form.email"
                />
                <InputError :message="errorFor('email') ?? form.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                    v-model="form.password"
                />
                <InputError
                    :message="errorFor('password') ?? form.errors.password"
                />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                    v-model="form.password_confirmation"
                />
                <InputError
                    :message="
                        errorFor('password_confirmation') ??
                        form.errors.password_confirmation
                    "
                />
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
                class="mt-2 w-full"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="6"
                >Log in</TextLink
            >
        </div>
    </form>
</template>
