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
            $table->string('legal_name', 160)->nullable()->after('status');
            $table->string('display_name', 160)->nullable()->after('legal_name');
            $table->string('legal_entity_type', 50)->nullable()->after('display_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropColumn([
                'legal_name',
                'display_name',
                'legal_entity_type',
            ]);
        });
    }
};
