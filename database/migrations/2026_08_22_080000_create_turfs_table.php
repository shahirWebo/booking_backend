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
        Schema::create('turfs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('location_id')
                ->constrained('locations')
                ->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('status', 50)->default('inactive');
            $table->string('surface_type', 50)->nullable();
            $table->boolean('is_indoor')->nullable();
            $table->unsignedSmallInteger('capacity_count')->nullable();
            $table->decimal('length_meters', 6, 2)->nullable();
            $table->decimal('width_meters', 6, 2)->nullable();
            $table->timestampsTz(6);

            $table->index('location_id', 'turfs_location_id_index');
            $table->index('status', 'turfs_status_index');
            $table->index(['location_id', 'status'], 'turfs_location_id_status_index');
            $table->index('surface_type', 'turfs_surface_type_index');
            $table->index('is_indoor', 'turfs_is_indoor_index');
        });

        DB::statement(
            "ALTER TABLE turfs ADD CONSTRAINT turfs_status_check CHECK (
                status IN ('active', 'inactive')
            )"
        );
        DB::statement(
            'ALTER TABLE turfs ADD CONSTRAINT turfs_capacity_count_positive_check CHECK (
                capacity_count IS NULL OR capacity_count > 0
            )'
        );
        DB::statement(
            'ALTER TABLE turfs ADD CONSTRAINT turfs_length_meters_positive_check CHECK (
                length_meters IS NULL OR length_meters > 0
            )'
        );
        DB::statement(
            'ALTER TABLE turfs ADD CONSTRAINT turfs_width_meters_positive_check CHECK (
                width_meters IS NULL OR width_meters > 0
            )'
        );
        DB::statement(
            'ALTER TABLE turfs ADD CONSTRAINT turfs_dimensions_paired_check CHECK (
                (length_meters IS NULL AND width_meters IS NULL)
                OR (length_meters IS NOT NULL AND width_meters IS NOT NULL)
            )'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turfs');
    }
};
