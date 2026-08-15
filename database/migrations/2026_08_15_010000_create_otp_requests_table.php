<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('otp_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->enum('purpose', [
                'authentication',
            ]);
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->unsignedSmallInteger('mobile_number_hash_key_version')->default(1);
            $table->char('mobile_number_lookup_hmac', 64);
            $table->text('mobile_number_ciphertext');
            $table->string('code_hash');
            $table->enum('status', [
                'pending_delivery',
                'provider_accepted',
                'delivery_failed',
                'delivery_unknown',
                'verified',
                'expired',
                'superseded',
                'attempt_limit_exhausted',
            ])->default('pending_delivery');
            $table->unsignedTinyInteger('failed_verification_attempts')->default(0);
            $table->string('terminal_reason', 64)->nullable();
            $table->string('delivery_provider_key', 64)->nullable();
            $table->string('delivery_provider_reference', 191)->nullable();
            $table->char('idempotency_key_hash', 64)->nullable()->unique('otp_requests_idempotency_key_unique');
            $table->char('request_fingerprint_hash', 64)->nullable();
            $table->char('delivery_effect_key', 64)->unique('otp_requests_delivery_effect_key_unique');
            $table->ulid('risk_correlation_id')->nullable()->index('otp_requests_risk_correlation_index');
            $table->ulid('audit_correlation_id')->nullable();
            $table->timestampTz('issued_at');
            $table->timestampTz('expires_at');
            $table->timestampTz('resend_available_at');
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampsTz();

            $table->index(['mobile_number_lookup_hmac', 'purpose'], 'otp_requests_mobile_purpose_index');
            $table->index(['status', 'expires_at'], 'otp_requests_status_expiry_index');
            $table->unique(
                ['delivery_provider_key', 'delivery_provider_reference'],
                'otp_requests_provider_reference_unique',
            );
        });

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX otp_requests_mobile_purpose_active_unique
            ON otp_requests (mobile_number_lookup_hmac, purpose)
            WHERE status IN ('pending_delivery', 'provider_accepted', 'delivery_unknown')
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_requests');
    }
};
