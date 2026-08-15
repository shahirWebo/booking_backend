<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('otp requests table stores secure challenge lifecycle fields', function () {
    expect(Schema::hasColumns('otp_requests', [
        'id',
        'purpose',
        'schema_version',
        'mobile_number_hash_key_version',
        'mobile_number_lookup_hmac',
        'mobile_number_ciphertext',
        'code_hash',
        'status',
        'failed_verification_attempts',
        'terminal_reason',
        'delivery_provider_key',
        'delivery_provider_reference',
        'idempotency_key_hash',
        'request_fingerprint_hash',
        'delivery_effect_key',
        'risk_correlation_id',
        'audit_correlation_id',
        'issued_at',
        'expires_at',
        'resend_available_at',
        'consumed_at',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    $request = otpRequestAttributes();

    DB::table('otp_requests')->insert($request);

    expect(DB::table('otp_requests')->where('id', $request['id'])->first())
        ->status->toBe('pending_delivery')
        ->failed_verification_attempts->toBe(0)
        ->schema_version->toBe(1)
        ->mobile_number_hash_key_version->toBe(1);
});

test('otp requests reject unsupported lifecycle statuses', function () {
    expect(fn () => DB::table('otp_requests')->insert(otpRequestAttributes([
        'status' => 'unknown',
    ])))->toThrow(QueryException::class);
});

test('otp requests reject unsupported purposes', function () {
    expect(fn () => DB::table('otp_requests')->insert(otpRequestAttributes([
        'purpose' => 'mobile_number_change',
    ])))->toThrow(QueryException::class);
});

test('otp requests allow only one active challenge per mobile lookup and purpose', function () {
    $activeRequest = otpRequestAttributes();

    DB::table('otp_requests')->insert($activeRequest);

    expect(fn () => DB::table('otp_requests')->insert(otpRequestAttributes([
        'mobile_number_lookup_hmac' => $activeRequest['mobile_number_lookup_hmac'],
        'purpose' => $activeRequest['purpose'],
        'status' => 'delivery_unknown',
    ])))->toThrow(QueryException::class);

    DB::table('otp_requests')
        ->where('id', $activeRequest['id'])
        ->update(['status' => 'superseded']);

    expect(DB::table('otp_requests')->insert(otpRequestAttributes([
        'mobile_number_lookup_hmac' => $activeRequest['mobile_number_lookup_hmac'],
        'purpose' => $activeRequest['purpose'],
    ])))->toBeTrue();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function otpRequestAttributes(array $overrides = []): array
{
    $now = now();

    return array_merge([
        'id' => (string) Str::ulid(),
        'purpose' => 'authentication',
        'mobile_number_lookup_hmac' => hash('sha256', (string) Str::uuid()),
        'mobile_number_ciphertext' => 'encrypted-test-value',
        'code_hash' => '$2y$12$test-only-placeholder-hash',
        'delivery_effect_key' => hash('sha256', (string) Str::uuid()),
        'issued_at' => $now,
        'expires_at' => $now->copy()->addMinutes(5),
        'resend_available_at' => $now->copy()->addMinute(),
        'created_at' => $now,
        'updated_at' => $now,
    ], $overrides);
}
