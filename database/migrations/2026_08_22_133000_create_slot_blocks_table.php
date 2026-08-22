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
        Schema::create('slot_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('turf_id')
                ->constrained('turfs')
                ->cascadeOnDelete();
            $table->date('block_date');
            $table->boolean('is_full_day')->default(false);
            $table->time('starts_at_time')->nullable();
            $table->time('ends_at_time')->nullable();
            $table->boolean('ends_next_day')->default(false);
            $table->string('reason', 255)->nullable();
            $table->timestampsTz(6);

            $table->index(['turf_id', 'block_date'], 'slot_blocks_turf_id_block_date_index');
            $table->index(['turf_id', 'is_full_day'], 'slot_blocks_turf_id_is_full_day_index');
        });

        DB::statement(
            'ALTER TABLE slot_blocks ADD CONSTRAINT slot_blocks_shape_check CHECK (
                (is_full_day = true AND starts_at_time IS NULL AND ends_at_time IS NULL AND ends_next_day = false)
                OR (
                    is_full_day = false
                    AND starts_at_time IS NOT NULL
                    AND ends_at_time IS NOT NULL
                    AND (
                        (ends_next_day = false AND ends_at_time > starts_at_time)
                        OR (ends_next_day = true AND ends_at_time < starts_at_time)
                    )
                )
            )'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slot_blocks');
    }
};
