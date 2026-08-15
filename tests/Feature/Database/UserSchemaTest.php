<?php

use App\Domain\Users\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('users table stores platform identity and account status fields', function () {
    expect(Schema::hasColumns('users', [
        'id',
        'name',
        'mobile_number',
        'email',
        'email_verified_at',
        'password',
        'status',
        'remember_token',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    $user = User::factory()->create([
        'mobile_number' => '+919876543210',
    ]);

    expect($user->status)->toBe(UserStatus::Active)
        ->and($user->mobile_number)->toBe('+919876543210')
        ->and(DB::table('users')->where('id', $user->id)->value('status'))->toBe('active');
});

test('users table supports mobile-only identities', function () {
    $userId = DB::table('users')->insertGetId([
        'mobile_number' => '+919876543211',
    ]);

    $user = DB::table('users')->where('id', $userId);

    expect($user->value('mobile_number'))->toBe('+919876543211')
        ->and($user->value('status'))->toBe('active')
        ->and($user->value('name'))->toBeNull()
        ->and($user->value('email'))->toBeNull()
        ->and($user->value('password'))->toBeNull();
});

test('users table enforces one account per normalized mobile number', function () {
    User::factory()->create([
        'mobile_number' => '+919876543212',
    ]);

    expect(fn () => User::factory()->create([
        'mobile_number' => '+919876543212',
    ]))->toThrow(QueryException::class);
});

test('users table permits multiple identities without a mobile number', function () {
    User::factory()->create(['mobile_number' => null]);
    User::factory()->create(['mobile_number' => null]);

    expect(DB::table('users')->whereNull('mobile_number')->count())->toBe(2);
});

test('users table rejects unsupported account statuses', function () {
    expect(fn () => DB::table('users')->insert([
        'name' => 'Invalid Status User',
        'email' => 'invalid-status@example.test',
        'password' => 'not-a-real-password-hash',
        'status' => 'unknown',
    ]))->toThrow(QueryException::class);
});
