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
        Schema::table('vendors', function (Blueprint $table): void {
            $table->boolean('is_gst_registered')->nullable()->after('primary_contact_mobile_number');
            $table->string('gstin', 15)->nullable()->after('is_gst_registered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropColumn(['is_gst_registered', 'gstin']);
        });
    }
};
