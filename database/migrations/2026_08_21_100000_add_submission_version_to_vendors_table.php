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
        Schema::table('vendors', function (Blueprint $table): void {
            $table->unsignedInteger('submission_version')->default(1)->after('status')
                ->comment('Current editable or reviewed onboarding submission version.');
            $table->index(['status', 'updated_at'], 'vendors_status_updated_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropIndex('vendors_status_updated_at_index');
            $table->dropColumn('submission_version');
        });
    }
};
