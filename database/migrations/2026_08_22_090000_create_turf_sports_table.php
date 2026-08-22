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
        Schema::create('turf_sports', function (Blueprint $table): void {
            $table->foreignId('turf_id')
                ->constrained('turfs')
                ->cascadeOnDelete();
            $table->foreignId('sport_id')
                ->constrained('sports')
                ->restrictOnDelete();

            $table->unique(
                ['turf_id', 'sport_id'],
                'turf_sports_turf_id_sport_id_unique'
            );
            $table->index('sport_id', 'turf_sports_sport_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turf_sports');
    }
};
