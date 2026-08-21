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
        Schema::table('customer_profiles', function (Blueprint $table): void {
            $table->string('profile_image_path')->nullable()->after('user_id');
            $table->json('preferred_sport_ids')->nullable()->after('profile_image_path');
            $table->string('default_location_label', 120)->nullable()->after('preferred_sport_ids');
            $table->boolean('email_notifications_enabled')->default(true)->after('default_location_label');
            $table->boolean('sms_notifications_enabled')->default(true)->after('email_notifications_enabled');
            $table->boolean('marketing_notifications_enabled')->default(false)->after('sms_notifications_enabled');
            $table->timestampTz('account_deletion_requested_at', 6)->nullable()->after('marketing_notifications_enabled');
            $table->text('account_deletion_reason')->nullable()->after('account_deletion_requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'profile_image_path',
                'preferred_sport_ids',
                'default_location_label',
                'email_notifications_enabled',
                'sms_notifications_enabled',
                'marketing_notifications_enabled',
                'account_deletion_requested_at',
                'account_deletion_reason',
            ]);
        });
    }
};
