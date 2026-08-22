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
        Schema::create('vendor_submission_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->unsignedInteger('submission_version');
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('snapshot');
            $table->timestampTz('submitted_at', 6);
            $table->timestampsTz(6);

            $table->unique(['vendor_id', 'submission_version'], 'vendor_submission_snapshots_vendor_version_unique');
            $table->index(['vendor_id', 'submitted_at'], 'vendor_submission_snapshots_vendor_submitted_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_submission_snapshots');
    }
};
