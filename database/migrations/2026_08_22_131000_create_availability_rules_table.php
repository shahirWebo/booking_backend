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
        Schema::create('availability_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('turf_id')
                ->constrained('turfs')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz(6);

            $table->unique(['turf_id', 'weekday'], 'availability_rules_turf_id_weekday_unique');
            $table->index(['turf_id', 'is_active'], 'availability_rules_turf_id_is_active_index');
        });

        DB::statement(
            'ALTER TABLE availability_rules ADD CONSTRAINT availability_rules_weekday_range_check CHECK (
                weekday >= 1 AND weekday <= 7
            )'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_rules');
    }
};
