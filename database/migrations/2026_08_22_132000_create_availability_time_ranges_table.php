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
        Schema::create('availability_time_ranges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('availability_rule_id')
                ->constrained('availability_rules')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence')->default(1);
            $table->time('starts_at_time');
            $table->time('ends_at_time');
            $table->boolean('ends_next_day')->default(false);
            $table->timestampsTz(6);

            $table->unique(
                ['availability_rule_id', 'sequence'],
                'availability_time_ranges_rule_id_sequence_unique'
            );
            $table->index(
                ['availability_rule_id', 'starts_at_time'],
                'availability_time_ranges_rule_id_starts_at_time_index'
            );
        });

        DB::statement(
            'ALTER TABLE availability_time_ranges ADD CONSTRAINT availability_time_ranges_time_window_check CHECK (
                (ends_next_day = false AND ends_at_time > starts_at_time)
                OR (ends_next_day = true AND ends_at_time < starts_at_time)
            )'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_time_ranges');
    }
};
