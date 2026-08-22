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
        Schema::create('maintenance_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('turf_id')
                ->constrained('turfs')
                ->cascadeOnDelete();
            $table->timestampTz('starts_at', 6);
            $table->timestampTz('ends_at', 6);
            $table->string('reason', 255)->nullable();
            $table->timestampsTz(6);

            $table->index(['turf_id', 'starts_at'], 'maintenance_blocks_turf_id_starts_at_index');
            $table->index(['turf_id', 'ends_at'], 'maintenance_blocks_turf_id_ends_at_index');
        });

        DB::statement(
            'ALTER TABLE maintenance_blocks ADD CONSTRAINT maintenance_blocks_time_window_check CHECK (
                ends_at > starts_at
            )'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_blocks');
    }
};
