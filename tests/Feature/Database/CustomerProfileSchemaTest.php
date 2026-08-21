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
