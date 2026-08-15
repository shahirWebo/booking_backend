<?php

namespace App\Domain\Auth\Services;

use App\Domain\Users\Enums\UserStatus;
use App\Models\OtpRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class AuthenticationAuditLogger
{
    /**
     * Record a minimized authentication audit event without phone numbers,
     * OTP values, or bearer credentials.
     */
    public function challengeIssued(OtpRequest $challenge): void
    {
        Log::info('auth.challenge.issued', [
            'challenge_id' => $challenge->id,
            'audit_correlation_id' => $challenge->audit_correlation_id,
            'purpose' => $challenge->purpose->value,
        ]);
    }

    public function authenticationSucceeded(OtpRequest $challenge, User $user): void
    {
        Log::info('auth.authentication.succeeded', [
            'challenge_id' => $challenge->id,
            'audit_correlation_id' => $challenge->audit_correlation_id,
            'user_id' => $user->id,
        ]);
    }

    public function authenticationFailed(string $challengeId, string $outcome): void
    {
        Log::warning('auth.authentication.failed', [
            'challenge_id' => $challengeId,
            'outcome' => $outcome,
        ]);
    }

    public function authenticationRestricted(OtpRequest $challenge, User $user): void
    {
        Log::warning('auth.authentication.restricted', [
            'challenge_id' => $challenge->id,
            'audit_correlation_id' => $challenge->audit_correlation_id,
            'user_id' => $user->id,
            'account_status' => $user->status->value,
        ]);
    }

    public function sessionLoggedOut(User $user): void
    {
        Log::info('auth.session.logged_out', [
            'user_id' => $user->id,
        ]);
    }

    public function sessionRevokedForRestrictedUser(User $user, UserStatus $status): void
    {
        Log::warning('auth.session.revoked_restricted_user', [
            'user_id' => $user->id,
            'account_status' => $status->value,
        ]);
    }
}
