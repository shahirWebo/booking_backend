<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->restrictOnDelete()
                ->comment('The vendor that owns this private payout account.');
            $table->string('account_holder_name', 150);
            $table->string('bank_name', 150);
            $table->text('account_number_encrypted');
            $table->string('account_number_last_four', 4);
            $table->text('routing_code_encrypted')->nullable();
            $table->string('country_code', 2);
            $table->string('currency', 3);
            $table->unsignedInteger('submission_version')->default(1);
            $table->string('status', 50)->default('active')
                ->comment('Account lifecycle: active, superseded, rejected.');
            $table->timestampsTz(6);

            $table->unique(
                ['vendor_id', 'account_number_last_four', 'submission_version'],
                'vendor_bank_accounts_vendor_last_four_version_unique',
            );
            $table->index(['vendor_id', 'status'], 'vendor_bank_accounts_vendor_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_bank_accounts');
    }
};
