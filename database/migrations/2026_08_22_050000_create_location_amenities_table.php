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
        Schema::create('location_amenities', function (Blueprint $table): void {
            $table->foreignId('location_id')
                ->constrained('locations')
                ->cascadeOnDelete();
            $table->foreignId('amenity_id')
                ->constrained('amenities')
                ->restrictOnDelete();

            $table->unique(
                ['location_id', 'amenity_id'],
                'location_amenities_location_id_amenity_id_unique'
            );
            $table->index('amenity_id', 'location_amenities_amenity_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_amenities');
    }
};
