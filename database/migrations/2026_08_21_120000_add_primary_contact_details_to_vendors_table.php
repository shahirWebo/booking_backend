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
            $table->string('primary_contact_name', 160)->nullable()->after('legal_entity_type');
            $table->string('primary_contact_email', 254)->nullable()->after('primary_contact_name');
            $table->string('primary_contact_mobile_number', 16)->nullable()->after('primary_contact_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropColumn([
                'primary_contact_name',
                'primary_contact_email',
                'primary_contact_mobile_number',
            ]);
        });
    }
};
