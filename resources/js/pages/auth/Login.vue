<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import FormFeedback from '@/components/feedback/FormFeedback.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { authApiService } from '@/lib/api/auth';
import type { AuthenticatedApiUser } from '@/lib/api/auth';
import { ApiClientError } from '@/lib/api/client';
import {
    getBrowserSessionState,
    initializeBrowserSession,
    clearBrowserSession,
    persistBrowserTokenSession,
    resolveBrowserSessionAuth,
} from '@/lib/browserSession';
import type { Auth, User } from '@/types/auth';

const page = usePage();
const browserSession = getBrowserSessionState();

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
    intendedUrl?: string | null;
    surfaceTitle?: string | null;
    surfaceDescription?: string | null;
}>();

type AuthStep = 'request' | 'verify';

const step = ref<AuthStep>('request');
const mobile = ref('');
const code = ref('');
const rememberMe = ref(true);
const otpRequestId = ref('');
const expiresAt = ref<string | null>(null);
const resendAvailableAt = ref<string | null>(null);
const requestError = ref<string | null>(null);
const verifyError = ref<string | null>(null);
const helperMessage = ref<string | null>(null);
const isRequestingOtp = ref(false);
const isVerifyingOtp = ref(false);
const now = ref(Date.now());
const hasRedirectedAuthenticatedUser = ref(false);

let ticker: number | null = null;
const effectiveAuth = computed(() =>
    resolveBrowserSessionAuth(page.props.auth),
);
const authenticatedDestination = computed(
    () =>
        props.intendedUrl ??
        (effectiveAuth.value.preferredSurface
            ? `/${effectiveAuth.value.preferredSurface}`
            : '/customer'),
);

const normalizedCode = computed(() =>
    code.value.replace(/\D/g, '').slice(0, 6),
);
const resendCountdown = computed(() =>
    getRemainingSeconds(resendAvailableAt.value),
);
const expiryCountdown = computed(() => getRemainingSeconds(expiresAt.value));
const isOtpExpired = computed(
    () => step.value === 'verify' && expiryCountdown.value === 0,
);
const canResendOtp = computed(
    () => step.value === 'verify' && resendCountdown.value === 0,
);
const maskedMobile = computed(() => maskMobileNumber(mobile.value));
const otpHint = computed(() => {
    if (isOtpExpired.value) {
        return 'That code has expired. Request a fresh OTP to continue.';
    }

    return `Enter the six-digit code sent to ${maskedMobile.value}.`;
});

watch(code, (nextValue) => {
    const digits = nextValue.replace(/\D/g, '').slice(0, 6);

    if (digits !== nextValue) {
        code.value = digits;
    }
});

onMounted(() => {
    initializeBrowserSession(page.props.auth);
    ticker = window.setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

watch(
    () => [
        effectiveAuth.value.user,
        browserSession.isRestoring,
        browserSession.hasRestored,
    ],
    () => {
        if (
            hasRedirectedAuthenticatedUser.value ||
            browserSession.isRestoring ||
            !effectiveAuth.value.user
        ) {
            return;
        }

        hasRedirectedAuthenticatedUser.value = true;
        window.location.replace(authenticatedDestination.value);
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (ticker !== null) {
        window.clearInterval(ticker);
    }
});

function getRemainingSeconds(timestamp: string | null): number {
    if (!timestamp) {
        return 0;
    }

    return Math.max(
        0,
        Math.ceil((new Date(timestamp).getTime() - now.value) / 1000),
    );
}

function formatCountdown(totalSeconds: number): string {
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

function maskMobileNumber(value: string): string {
    const visibleDigits = value.replace(/\D/g, '');

    if (visibleDigits.length < 4) {
        return value || 'your mobile number';
    }

    return `${value
        .slice(0, Math.max(0, value.length - 4))
        .replace(/\d/g, '•')}${visibleDigits.slice(-4)}`;
}

function resetVerificationState(): void {
    code.value = '';
    otpRequestId.value = '';
    expiresAt.value = null;
    resendAvailableAt.value = null;
    verifyError.value = null;
}

function goBackToMobileEntry(): void {
    step.value = 'request';
    helperMessage.value = null;
    requestError.value = null;
    resetVerificationState();
}

async function requestOtp(options: { resend?: boolean } = {}): Promise<void> {
    const trimmedMobile = mobile.value.trim();
    const isResend = options.resend === true;

    requestError.value = null;
    verifyError.value = null;
    helperMessage.value = null;

    if (!trimmedMobile) {
        requestError.value = 'Enter a valid mobile number to continue.';

        return;
    }

    isRequestingOtp.value = true;

    try {
        const response = await authApiService.requestOtp({
            mobile: trimmedMobile,
        });

        if (!response) {
            throw new Error('OTP request did not return a response.');
        }

        mobile.value = trimmedMobile;
        otpRequestId.value = response.otp_request_id;
        expiresAt.value = response.expires_at;
        resendAvailableAt.value = response.resend_available_at;
        code.value = '';
        step.value = 'verify';
        helperMessage.value = isResend
            ? 'A fresh OTP is on its way. Use the newest code only.'
            : `We sent a secure OTP to ${maskMobileNumber(trimmedMobile)}.`;
    } catch (error) {
        if (error instanceof ApiClientError) {
            requestError.value =
                error.fieldErrors.mobile?.[0] ??
                (error.status === 429
                    ? 'OTP requests are temporarily limited. Please wait a moment and try again.'
                    : 'We could not send your OTP right now. Please try again.');

            return;
        }

        requestError.value =
            'We could not send your OTP right now. Please check your connection and try again.';
    } finally {
        isRequestingOtp.value = false;
    }
}

async function verifyOtp(): Promise<void> {
    verifyError.value = null;
    helperMessage.value = null;

    if (isOtpExpired.value) {
        verifyError.value =
            'This OTP has expired. Request a new code to continue.';

        return;
    }

    if (normalizedCode.value.length !== 6) {
        verifyError.value = 'Enter the complete six-digit OTP.';

        return;
    }

    isVerifyingOtp.value = true;

    try {
        const verification = await authApiService.verifyOtp(
            {
                otp_request_id: otpRequestId.value,
                code: normalizedCode.value,
            },
            {
                headers: {
                    'X-Client-Mode': 'web',
                },
            },
        );

        if (!verification) {
            throw new Error('OTP verification did not return a response.');
        }

        persistBrowserTokenSession({
            accessToken: verification.access_token,
            auth: {
                user: null,
                roles: ['customer'],
                permissions: [],
                preferredSurface: 'customer',
                sessionMode: 'token',
            },
            persistence: rememberMe.value ? 'local' : 'session',
        });

        const authenticatedUser = await authApiService.fetchCurrentUser();

        if (!authenticatedUser) {
            throw new Error('Authenticated user payload is unavailable.');
        }

        persistBrowserTokenSession({
            accessToken: verification.access_token,
            auth: createCustomerTokenAuth(authenticatedUser),
            persistence: rememberMe.value ? 'local' : 'session',
        });

        window.location.replace(authenticatedDestination.value);
    } catch (error) {
        clearBrowserSession();

        if (error instanceof ApiClientError) {
            if (error.code === 'USER_BLOCKED') {
                verifyError.value =
                    'This account is blocked. Please contact support for help.';

                return;
            }

            if (error.code === 'USER_SUSPENDED') {
                verifyError.value =
                    'This account is suspended right now. Please contact support for help.';

                return;
            }

            verifyError.value =
                error.fieldErrors.code?.[0] ??
                'The OTP is invalid or expired. Request a fresh code and try again.';

            return;
        }

        verifyError.value =
            'We could not verify your OTP right now. Please try again in a moment.';
    } finally {
        isVerifyingOtp.value = false;
    }
}

function createCustomerTokenAuth(user: AuthenticatedApiUser): Auth {
    const authUser: User = {
        id: user.id,
        name: user.name,
        email: user.email,
        email_verified_at: null,
        mobile_number: user.mobile_number,
        status: user.status,
    };

    return {
        user: authUser,
        roles: ['customer'],
        permissions: [],
        preferredSurface: 'customer',
        sessionMode: 'token',
    };
}
</script>

<template>
    <div class="">
        <div
            class="mx-auto mb-6 flex h-1.5 w-16 rounded-full bg-slate-300/70 lg:hidden"
        />

        <div class="flex items-center justify-between gap-4">
            <div>
                <p
                    class="text-[0.7rem] font-semibold tracking-[0.26em] text-slate-500 uppercase"
                >
                    {{ step === 'request' ? 'Step 1 of 2' : 'Step 2 of 2' }}
                </p>
                <h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em]">
                    {{
                        step === 'request'
                            ? (props.surfaceTitle ?? 'Log in with your mobile')
                            : 'Verify your code'
                    }}
                </h2>
            </div>

            <div class="flex gap-2">
                <span
                    class="h-2.5 w-8 rounded-full"
                    :class="
                        step === 'request' ? 'bg-[#7cca4c]' : 'bg-[#d9e8cb]'
                    "
                />
                <span
                    class="h-2.5 w-8 rounded-full"
                    :class="step === 'verify' ? 'bg-[#7cca4c]' : 'bg-[#d9e8cb]'"
                />
            </div>
        </div>

        <div v-if="status" class="mt-5">
            <FormFeedback :message="status" variant="success" />
        </div>

        <div v-if="helperMessage" class="mt-5">
            <FormFeedback :message="helperMessage" variant="success" />
        </div>

        <div v-if="step === 'request'" class="mt-6">
            <p class="text-sm leading-6 text-slate-500">
                {{
                    props.surfaceDescription ??
                    'Use the same mobile number you book with. We’ll send a one-time password to confirm it’s really you.'
                }}
            </p>

            <div class="mt-6 space-y-5">
                <div class="space-y-2">
                    <Label for="mobile">Mobile number</Label>
                    <Input
                        id="mobile"
                        v-model="mobile"
                        type="tel"
                        inputmode="tel"
                        autocomplete="tel"
                        placeholder="+91 98765 43210"
                        class="h-12 rounded-2xl border-slate-200 bg-white px-4 text-base shadow-none"
                    />
                    <InputError :message="requestError ?? undefined" />
                </div>

                <Label
                    for="remember-mobile-otp"
                    class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600"
                >
                    <Checkbox id="remember-mobile-otp" v-model="rememberMe" />
                    Keep this device signed in after verification
                </Label>

                <Button
                    type="button"
                    class="h-12 w-full rounded-2xl bg-[#7cca4c] text-base font-semibold text-slate-950 hover:bg-[#72ba45]"
                    :disabled="isRequestingOtp"
                    data-test="request-otp-button"
                    @click="requestOtp()"
                >
                    <Spinner v-if="isRequestingOtp" />
                    Generate OTP
                </Button>
            </div>

            <p class="mt-6 text-center text-sm leading-6 text-slate-500">
                {{
                    props.surfaceTitle === 'Vendor access'
                        ? 'The same verified mobile number will take you back into your vendor onboarding workspace after OTP verification.'
                        : 'New to Spotz? Your customer account can continue from the same mobile number after OTP verification.'
                }}
            </p>
        </div>

        <div v-else class="mt-6">
            <div class="rounded-[1.75rem] bg-[#eef5e5] p-4">
                <p class="text-sm font-medium text-slate-700">
                    {{ maskedMobile }}
                </p>
                <p class="mt-1 text-sm leading-6 text-slate-500">
                    {{ otpHint }}
                </p>
            </div>

            <div class="mt-6 flex justify-center">
                <InputOTP
                    v-model="code"
                    :maxlength="6"
                    :disabled="isVerifyingOtp"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    data-test="otp-input"
                >
                    <InputOTPGroup>
                        <InputOTPSlot
                            v-for="index in 6"
                            :key="index"
                            :index="index - 1"
                            class="h-12 w-12 rounded-2xl border border-[#d5e8c5] bg-[#f7fbf2] text-lg font-semibold first:rounded-2xl first:border last:rounded-2xl last:border"
                        />
                    </InputOTPGroup>
                </InputOTP>
            </div>

            <div class="mt-4 space-y-2 text-center">
                <p class="text-sm text-slate-500">
                    OTP expires in
                    <span class="font-semibold text-slate-800">
                        {{ formatCountdown(expiryCountdown) }}
                    </span>
                </p>
                <InputError :message="verifyError ?? undefined" />
            </div>

            <Button
                type="button"
                class="mt-6 h-12 w-full rounded-2xl bg-[#7cca4c] text-base font-semibold text-slate-950 hover:bg-[#72ba45]"
                :disabled="isVerifyingOtp || normalizedCode.length !== 6"
                data-test="verify-otp-button"
                @click="verifyOtp"
            >
                <Spinner v-if="isVerifyingOtp" />
                Verify OTP
            </Button>

            <div class="mt-5 text-center text-sm leading-6 text-slate-500">
                <p>
                    Didn’t receive it?
                    <button
                        type="button"
                        class="ml-1 font-semibold text-slate-900 disabled:text-slate-400"
                        :disabled="!canResendOtp || isRequestingOtp"
                        data-test="resend-otp-button"
                        @click="requestOtp({ resend: true })"
                    >
                        Resend OTP
                    </button>
                </p>
                <p v-if="!canResendOtp" class="mt-1">
                    Resend available in
                    <span class="font-semibold text-slate-800">
                        {{ formatCountdown(resendCountdown) }}
                    </span>
                </p>
            </div>

            <button
                type="button"
                class="mt-6 w-full text-center text-sm font-medium text-slate-600 underline decoration-slate-300 underline-offset-4"
                @click="goBackToMobileEntry"
            >
                Change mobile number
            </button>
        </div>
    </div>
</template>
