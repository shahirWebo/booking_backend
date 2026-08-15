<?php

use App\Domain\Auth\Enums\OtpRequestPurpose;
use App\Domain\Auth\Enums\OtpRequestStatus;
use App\Models\OtpRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('otp request model generates a ulid and casts challenge fields', function () {
    $issuedAt = now();
    $otpRequest = OtpRequest::query()->create([
        'purpose' => OtpRequestPurpose::Authentication,
        'mobile_number_lookup_hmac' => hash('sha256', (string) Str::uuid()),
        'mobile_number_ciphertext' => '+919876543210',
        'code_hash' => '$2y$12$test-only-placeholder-hash',
        'delivery_effect_key' => hash('sha256', (string) Str::uuid()),
        'issued_at' => $issuedAt,
        'expires_at' => $issuedAt->copy()->addMinutes(5),
        'resend_available_at' => $issuedAt->copy()->addMinute(),
    ]);
    $otpRequest->refresh();

    expect(Str::isUlid($otpRequest->id))->toBeTrue()
        ->and($otpRequest->purpose)->toBe(OtpRequestPurpose::Authentication)
        ->and($otpRequest->status)->toBe(OtpRequestStatus::PendingDelivery)
        ->and($otpRequest->failed_verification_attempts)->toBe(0)
        ->and($otpRequest->issued_at->timestamp)->toBe($issuedAt->timestamp)
        ->and($otpRequest->expires_at->timestamp)->toBe($issuedAt->copy()->addMinutes(5)->timestamp);
});

test('otp request model encrypts mobile numbers and hides private attributes', function () {
    $mobileNumber = '+919876543210';
    $otpRequest = OtpRequest::query()->create([
        'purpose' => OtpRequestPurpose::Authentication,
        'mobile_number_lookup_hmac' => hash('sha256', (string) Str::uuid()),
        'mobile_number_ciphertext' => $mobileNumber,
        'code_hash' => '$2y$12$test-only-placeholder-hash',
        'delivery_effect_key' => hash('sha256', (string) Str::uuid()),
        'issued_at' => now(),
        'expires_at' => now()->addMinutes(5),
        'resend_available_at' => now()->addMinute(),
    ]);

    $storedMobileNumber = DB::table('otp_requests')
        ->where('id', $otpRequest->id)
        ->value('mobile_number_ciphertext');

    expect($storedMobileNumber)->not->toBe($mobileNumber)
        ->and($otpRequest->fresh()->mobile_number_ciphertext)->toBe($mobileNumber)
        ->and($otpRequest->toArray())->not->toHaveKeys([
            'mobile_number_lookup_hmac',
            'mobile_number_ciphertext',
            'code_hash',
            'status',
            'delivery_provider_reference',
            'idempotency_key_hash',
            'request_fingerprint_hash',
            'delivery_effect_key',
        ]);
});
