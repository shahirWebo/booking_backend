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
        Schema::table('sports', function (Blueprint $table) {
            $table->string('icon_asset_key')->nullable()->after('is_active');
            $table->string('icon_alt_text')->nullable()->after('icon_asset_key');
            $table->string('image_asset_key')->nullable()->after('icon_alt_text');
            $table->string('image_alt_text')->nullable()->after('image_asset_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            $table->dropColumn([
                'icon_asset_key',
                'icon_alt_text',
                'image_asset_key',
                'image_alt_text',
            ]);
        });
    }
};
