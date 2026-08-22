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
        Schema::create('turf_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('turf_id')
                ->constrained('turfs')
                ->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('description');
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz(6);

            $table->unique(
                ['turf_id', 'sort_order'],
                'turf_rules_turf_id_sort_order_unique'
            );
            $table->index(['turf_id', 'is_active'], 'turf_rules_turf_id_is_active_index');
        });

        DB::statement(
            'ALTER TABLE turf_rules ADD CONSTRAINT turf_rules_sort_order_positive_check CHECK (
                sort_order > 0
            )'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turf_rules');
    }
};
