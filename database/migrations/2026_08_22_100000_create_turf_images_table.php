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
        Schema::create('turf_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('turf_id')
                ->constrained('turfs')
                ->cascadeOnDelete();
            $table->foreignId('file_id')
                ->constrained('files')
                ->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('caption', 255)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->timestampsTz(6);

            $table->unique(
                ['turf_id', 'file_id'],
                'turf_images_turf_id_file_id_unique'
            );
            $table->index(
                ['turf_id', 'sort_order'],
                'turf_images_turf_id_sort_order_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turf_images');
    }
};
