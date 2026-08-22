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
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->restrictOnDelete();
            $table->string('name', 150);
            $table->string('address_line_1', 255);
            $table->string('address_line_2', 255)->nullable();
            $table->string('landmark', 255)->nullable();
            $table->string('locality', 150)->nullable();
            $table->string('city', 120);
            $table->string('state', 120);
            $table->string('postal_code', 20);
            $table->char('country_code', 2);
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->string('timezone', 100);
            $table->timestampsTz(6);

            $table->index('vendor_id', 'locations_vendor_id_index');
            $table->index(['latitude', 'longitude'], 'locations_latitude_longitude_idx');
        });

        DB::statement(
            'ALTER TABLE locations ADD CONSTRAINT locations_coordinates_paired_check CHECK (
                (latitude IS NULL AND longitude IS NULL)
                OR (latitude IS NOT NULL AND longitude IS NOT NULL)
            )'
        );
        DB::statement(
            'ALTER TABLE locations ADD CONSTRAINT locations_latitude_range_check CHECK (
                latitude IS NULL OR (latitude >= -90 AND latitude <= 90)
            )'
        );
        DB::statement(
            'ALTER TABLE locations ADD CONSTRAINT locations_longitude_range_check CHECK (
                longitude IS NULL OR (longitude >= -180 AND longitude <= 180)
            )'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
