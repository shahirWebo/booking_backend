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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('status', 50)->default('draft')
                ->comment('Vendor lifecycle status: draft, pending_approval, approved, rejected, suspended, inactive');
            $table->timestampsTz();
        });

        // Add check constraint for valid status values
        DB::statement(
            "ALTER TABLE vendors ADD CONSTRAINT vendors_status_check CHECK (status IN ('draft', 'pending_approval', 'approved', 'rejected', 'suspended', 'inactive'))"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
