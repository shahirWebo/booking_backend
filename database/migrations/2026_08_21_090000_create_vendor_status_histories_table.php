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
        Schema::create('vendor_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->restrictOnDelete()
                ->comment('The vendor whose lifecycle transition was recorded.');
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('The authenticated actor, or null for a system transition.');
            $table->unsignedBigInteger('sequence')
                ->comment('Monotonic, vendor-scoped sequence assigned by the transition action.');
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->string('reason_code', 100)->nullable();
            $table->text('reason_message')->nullable()
                ->comment('Controlled, audience-safe explanation; never internal investigation detail.');
            $table->uuid('correlation_id')->nullable();
            $table->timestampTz('transitioned_at', 6);
            $table->timestampsTz(6);

            $table->unique(['vendor_id', 'sequence'], 'vendor_status_histories_vendor_sequence_unique');
            $table->index(['vendor_id', 'transitioned_at'], 'vendor_status_histories_vendor_transitioned_index');
            $table->index('correlation_id', 'vendor_status_histories_correlation_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_status_histories');
    }
};
