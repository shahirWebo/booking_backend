<?php

use App\Domain\Users\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the legacy profile url redirects to the customer profile page', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
    ]);

    $this->actingAs($user)
        ->get('/profile')
        ->assertRedirect(route('customer.profile.show'));
});
