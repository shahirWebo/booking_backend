<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('customer profiles table stores one profile per user', function () {
    expect(Schema::hasColumns('customer_profiles', [
        'id',
        'user_id',
        'profile_image_path',
        'preferred_sport_ids',
        'default_location_label',
        'email_notifications_enabled',
        'sms_notifications_enabled',
        'marketing_notifications_enabled',
        'account_deletion_requested_at',
        'account_deletion_reason',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    $user = User::factory()->create();

    DB::table('customer_profiles')->insert([
        'user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('customer_profiles')->where('user_id', $user->id)->exists())->toBeTrue();

    expect(fn () => DB::table('customer_profiles')->insert([
        'user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('customer profiles are deleted when their user is deleted', function () {
    $user = User::factory()->create();

    DB::table('customer_profiles')->insert([
        'user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user->delete();

    expect(DB::table('customer_profiles')->where('user_id', $user->id)->exists())->toBeFalse();
});

test('customer profiles store preference defaults and account deletion metadata', function () {
    $user = User::factory()->create();

    DB::table('customer_profiles')->insert([
        'user_id' => $user->id,
        'preferred_sport_ids' => json_encode([1, 2], JSON_THROW_ON_ERROR),
        'default_location_label' => 'South Delhi',
        'email_notifications_enabled' => true,
        'sms_notifications_enabled' => false,
        'marketing_notifications_enabled' => false,
        'account_deletion_requested_at' => now(),
        'account_deletion_reason' => 'Need a break from booking.',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $profile = DB::table('customer_profiles')->where('user_id', $user->id)->first();

    expect($profile)->not->toBeNull()
        ->and($profile->default_location_label)->toBe('South Delhi')
        ->and((bool) $profile->email_notifications_enabled)->toBeTrue()
        ->and((bool) $profile->sms_notifications_enabled)->toBeFalse()
        ->and((bool) $profile->marketing_notifications_enabled)->toBeFalse()
        ->and($profile->account_deletion_reason)->toBe('Need a break from booking.');
});
