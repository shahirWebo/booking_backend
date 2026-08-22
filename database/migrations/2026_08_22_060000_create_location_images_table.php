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
        Schema::create('location_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('location_id')
                ->constrained('locations')
                ->cascadeOnDelete();
            $table->foreignId('file_id')
                ->constrained('files')
                ->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('caption', 255)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->timestampsTz(6);

            $table->unique(
                ['location_id', 'file_id'],
                'location_images_location_id_file_id_unique'
            );
            $table->index(
                ['location_id', 'sort_order'],
                'location_images_location_id_sort_order_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_images');
    }
};
