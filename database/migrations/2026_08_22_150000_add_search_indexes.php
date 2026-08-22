<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->index(['status', 'city', 'locality'], 'locations_status_city_locality_index');
        });

        Schema::table('turfs', function (Blueprint $table): void {
            $table->index('name', 'turfs_name_index');
        });

        Schema::table('pricing_rules', function (Blueprint $table): void {
            $table->index(['turf_id', 'is_active', 'price_minor'], 'pricing_rules_turf_id_is_active_price_minor_index');
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table): void {
            $table->dropIndex('pricing_rules_turf_id_is_active_price_minor_index');
        });

        Schema::table('turfs', function (Blueprint $table): void {
            $table->dropIndex('turfs_name_index');
        });

        Schema::table('locations', function (Blueprint $table): void {
            $table->dropIndex('locations_status_city_locality_index');
        });
    }
};
