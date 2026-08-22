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
        Schema::create('pricing_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('turf_id')
                ->constrained('turfs')
                ->cascadeOnDelete();
            $table->string('rule_type', 50);
            $table->bigInteger('price_minor');
            $table->string('currency', 3);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->date('effective_from_date')->nullable();
            $table->date('effective_until_date')->nullable();
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->date('special_date')->nullable();
            $table->time('starts_at_time')->nullable();
            $table->time('ends_at_time')->nullable();
            $table->boolean('ends_next_day')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz(6);

            $table->index(
                ['turf_id', 'is_active', 'priority'],
                'pricing_rules_turf_id_is_active_priority_index'
            );
            $table->index(
                ['turf_id', 'rule_type', 'effective_from_date'],
                'pricing_rules_turf_id_rule_type_effective_from_date_index'
            );
            $table->index(
                ['turf_id', 'special_date'],
                'pricing_rules_turf_id_special_date_index'
            );
        });

        DB::statement(
            "ALTER TABLE pricing_rules ADD CONSTRAINT pricing_rules_rule_type_check CHECK (
                rule_type IN ('base', 'weekday', 'weekend', 'peak_hour', 'special_date')
            )"
        );
        DB::statement(
            'ALTER TABLE pricing_rules ADD CONSTRAINT pricing_rules_price_minor_bounds_check CHECK (
                price_minor >= 0 AND price_minor <= 900000000000000
            )'
        );
        DB::statement(
            'ALTER TABLE pricing_rules ADD CONSTRAINT pricing_rules_currency_format_check CHECK (
                length(currency) = 3 AND currency = upper(currency)
            )'
        );
        DB::statement(
            'ALTER TABLE pricing_rules ADD CONSTRAINT pricing_rules_priority_positive_check CHECK (
                priority > 0
            )'
        );
        DB::statement(
            'ALTER TABLE pricing_rules ADD CONSTRAINT pricing_rules_weekday_range_check CHECK (
                weekday IS NULL OR (weekday >= 1 AND weekday <= 7)
            )'
        );
        DB::statement(
            'ALTER TABLE pricing_rules ADD CONSTRAINT pricing_rules_effective_date_range_check CHECK (
                effective_from_date IS NULL
                OR effective_until_date IS NULL
                OR effective_until_date >= effective_from_date
            )'
        );
        DB::statement(
            'ALTER TABLE pricing_rules ADD CONSTRAINT pricing_rules_peak_hour_time_window_check CHECK (
                (rule_type = \'peak_hour\' AND starts_at_time IS NOT NULL AND ends_at_time IS NOT NULL AND (
                    (ends_next_day = false AND ends_at_time > starts_at_time)
                    OR (ends_next_day = true AND ends_at_time < starts_at_time)
                ))
                OR (rule_type <> \'peak_hour\' AND starts_at_time IS NULL AND ends_at_time IS NULL AND ends_next_day = false)
            )'
        );
        DB::statement(
            'ALTER TABLE pricing_rules ADD CONSTRAINT pricing_rules_selector_shape_check CHECK (
                (rule_type = \'base\' AND weekday IS NULL AND special_date IS NULL)
                OR (rule_type = \'weekday\' AND weekday IS NOT NULL AND special_date IS NULL)
                OR (rule_type = \'weekend\' AND weekday IS NULL AND special_date IS NULL)
                OR (rule_type = \'peak_hour\' AND weekday IS NULL AND special_date IS NULL)
                OR (rule_type = \'special_date\' AND weekday IS NULL AND special_date IS NOT NULL)
            )'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
