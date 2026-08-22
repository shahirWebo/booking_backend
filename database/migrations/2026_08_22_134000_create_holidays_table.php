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
        Schema::create('holidays', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('location_id')
                ->constrained('locations')
                ->cascadeOnDelete();
            $table->date('holiday_date');
            $table->string('name', 150)->nullable();
            $table->string('reason', 255)->nullable();
            $table->boolean('is_closed')->default(true);
            $table->timestampsTz(6);

            $table->unique(['location_id', 'holiday_date'], 'holidays_location_id_holiday_date_unique');
            $table->index(['location_id', 'is_closed'], 'holidays_location_id_is_closed_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
