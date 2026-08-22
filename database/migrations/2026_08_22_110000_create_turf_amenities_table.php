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
        Schema::create('turf_amenities', function (Blueprint $table): void {
            $table->foreignId('turf_id')
                ->constrained('turfs')
                ->cascadeOnDelete();
            $table->foreignId('amenity_id')
                ->constrained('amenities')
                ->restrictOnDelete();

            $table->unique(
                ['turf_id', 'amenity_id'],
                'turf_amenities_turf_id_amenity_id_unique'
            );
            $table->index('amenity_id', 'turf_amenities_amenity_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turf_amenities');
    }
};
