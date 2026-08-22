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
        Schema::table('turfs', function (Blueprint $table): void {
            $table->unsignedSmallInteger('booking_lead_time_minutes')
                ->default(0)
                ->after('width_meters');
            $table->unsignedSmallInteger('advance_booking_window_days')
                ->default(30)
                ->after('booking_lead_time_minutes');
            $table->unsignedSmallInteger('default_slot_duration_minutes')
                ->default(60)
                ->after('advance_booking_window_days');
            $table->unsignedSmallInteger('min_booking_duration_minutes')
                ->default(60)
                ->after('default_slot_duration_minutes');
            $table->unsignedSmallInteger('max_booking_duration_minutes')
                ->default(240)
                ->after('min_booking_duration_minutes');

            $table->index(
                ['location_id', 'default_slot_duration_minutes'],
                'turfs_location_id_default_slot_duration_minutes_index'
            );
        });

        DB::statement(
            'ALTER TABLE turfs ADD CONSTRAINT turfs_advance_booking_window_days_positive_check CHECK (
                advance_booking_window_days > 0
            )'
        );
        DB::statement(
            'ALTER TABLE turfs ADD CONSTRAINT turfs_default_slot_duration_minutes_positive_check CHECK (
                default_slot_duration_minutes > 0
            )'
        );
        DB::statement(
            'ALTER TABLE turfs ADD CONSTRAINT turfs_min_booking_duration_minutes_positive_check CHECK (
                min_booking_duration_minutes > 0
            )'
        );
        DB::statement(
            'ALTER TABLE turfs ADD CONSTRAINT turfs_max_booking_duration_minutes_positive_check CHECK (
                max_booking_duration_minutes > 0
            )'
        );
        DB::statement(
            'ALTER TABLE turfs ADD CONSTRAINT turfs_booking_duration_bounds_check CHECK (
                min_booking_duration_minutes <= max_booking_duration_minutes
                AND default_slot_duration_minutes >= min_booking_duration_minutes
                AND default_slot_duration_minutes <= max_booking_duration_minutes
            )'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turfs', function (Blueprint $table): void {
            $table->dropIndex('turfs_location_id_default_slot_duration_minutes_index');
            $table->dropColumn([
                'booking_lead_time_minutes',
                'advance_booking_window_days',
                'default_slot_duration_minutes',
                'min_booking_duration_minutes',
                'max_booking_duration_minutes',
            ]);
        });
    }
};
