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
        Schema::create('vendor_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->cascadeOnDelete()
                ->comment('The vendor this membership belongs to');
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('The user who has this vendor membership');
            $table->string('role', 50)
                ->comment('The role of this user within the vendor (vendor_owner, vendor_manager, vendor_staff, vendor_accountant)');
            $table->string('status', 50)->default('active')
                ->comment('Membership status: active, inactive');
            $table->timestampsTz();

            // Index for efficient lookup of vendor staff
            $table->index('vendor_id', 'vendor_memberships_vendor_id_index');
            // Index for efficient lookup of user memberships
            $table->index('user_id', 'vendor_memberships_user_id_index');
        });

        // Add check constraint for valid status values
        DB::statement(
            "ALTER TABLE vendor_memberships ADD CONSTRAINT vendor_memberships_status_check CHECK (status IN ('active', 'inactive'))"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_memberships');
    }
};
