<?php

namespace App\Models;

use App\Domain\Auth\Enums\OtpRequestPurpose;
use App\Domain\Auth\Enums\OtpRequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property OtpRequestPurpose $purpose
 * @property int $schema_version
 * @property int $mobile_number_hash_key_version
 * @property string $mobile_number_lookup_hmac
 * @property string $mobile_number_ciphertext
 * @property string $code_hash
 * @property OtpRequestStatus $status
 * @property int $failed_verification_attempts
 * @property string|null $terminal_reason
 * @property string|null $delivery_provider_key
 * @property string|null $delivery_provider_reference
 * @property string|null $idempotency_key_hash
 * @property string|null $request_fingerprint_hash
 * @property string $delivery_effect_key
 * @property string|null $risk_correlation_id
 * @property string|null $audit_correlation_id
 * @property Carbon $issued_at
 * @property Carbon $expires_at
 * @property Carbon $resend_available_at
 * @property Carbon|null $consumed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
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
])]
#[Hidden([
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
])]
class OtpRequest extends Model
{
    use HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => OtpRequestPurpose::class,
            'schema_version' => 'integer',
            'mobile_number_hash_key_version' => 'integer',
            'mobile_number_ciphertext' => 'encrypted',
            'status' => OtpRequestStatus::class,
            'failed_verification_attempts' => 'integer',
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
