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
        Schema::create('location_operating_hours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('location_id')
                ->constrained('locations')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->unsignedTinyInteger('sequence')->default(1);
            $table->time('opens_at_time');
            $table->time('closes_at_time');
            $table->boolean('ends_next_day')->default(false);
            $table->timestampsTz(6);

            $table->unique(
                ['location_id', 'weekday', 'sequence'],
                'location_operating_hours_location_id_weekday_sequence_unique'
            );
            $table->index(
                ['location_id', 'weekday'],
                'location_operating_hours_location_id_weekday_index'
            );
        });

        DB::statement(
            'ALTER TABLE location_operating_hours ADD CONSTRAINT location_operating_hours_weekday_range_check CHECK (
                weekday >= 1 AND weekday <= 7
            )'
        );
        DB::statement(
            'ALTER TABLE location_operating_hours ADD CONSTRAINT location_operating_hours_time_window_check CHECK (
                (ends_next_day = 0 AND closes_at_time > opens_at_time)
                OR (ends_next_day = 1 AND closes_at_time < opens_at_time)
            )'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_operating_hours');
    }
};
