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
        Schema::create('files', function (Blueprint $table): void {
            $table->id();
            $table->string('purpose', 50);
            $table->string('status', 50);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->restrictOnDelete();
            $table->string('logical_disk', 50);
            $table->string('object_key', 255)->unique();
            $table->string('original_name', 255)->nullable();
            $table->string('detected_mime_type', 100)->nullable();
            $table->string('canonical_extension', 10)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->char('checksum_sha256', 64)->nullable();
            $table->unsignedInteger('processing_version')->default(1);
            $table->timestampTz('uploaded_at')->nullable();
            $table->timestampTz('scanned_at')->nullable();
            $table->timestampTz('ready_at')->nullable();
            $table->timestampTz('rejected_at')->nullable();
            $table->timestampTz('deleted_at')->nullable();
            $table->string('rejection_code', 100)->nullable();
            $table->timestampsTz(6);

            $table->index(['vendor_id', 'purpose', 'status'], 'files_vendor_purpose_status_index');
            $table->index(['status', 'created_at'], 'files_status_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
