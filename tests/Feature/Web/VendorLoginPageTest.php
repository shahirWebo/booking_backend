<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the vendor login page returns users to vendor onboarding after authentication', function (): void {
    $this->get(route('vendor.login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->where('intendedUrl', route('vendor.onboarding.show')),
        );
});
