<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

it('registers product surface routes with dedicated URI and name prefixes', function (): void {
    $routes = [
        'home' => ['GET', '/', 'Welcome'],
        'customer.home' => ['GET', 'customer', 'customer/Home'],
        'customer.profile.show' => ['GET', 'customer/profile', null],
        'vendor.home' => ['GET', 'vendor', 'vendor/Home'],
        'vendor.login' => ['GET', 'vendor/login', null],
        'admin.home' => ['GET', 'admin', 'admin/Home'],
        'admin.sports.index' => ['GET', 'admin/operations/sports', null],
        'admin.sports.create' => ['GET', 'admin/operations/sports/create', null],
        'admin.amenities.index' => ['GET', 'admin/operations/amenities', null],
        'admin.amenities.create' => ['GET', 'admin/operations/amenities/create', null],
        'admin.system_settings.show' => ['GET', 'admin/governance/system-settings', null],
        'admin.login' => ['GET', 'admin/login', null],
    ];

    foreach ($routes as $name => [$method, $uri, $component]) {
        $route = Route::getRoutes()->getByName($name);

        expect($route)
            ->not->toBeNull()
            ->and($route->methods())->toContain($method)
            ->and($route->uri())->toBe($uri);

        if ($component !== null) {
            expect($route->defaults['component'] ?? null)->toBe($component);
        }
    }
});

it('renders the public surface overview pages', function (string $routeName, string $component): void {
    $this->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component),
        );
})->with([
    ['home', 'Welcome'],
    ['customer.home', 'customer/Home'],
    ['vendor.home', 'vendor/Home'],
    ['vendor.login', 'auth/Login'],
    ['admin.home', 'admin/Home'],
    ['admin.login', 'auth/AdminLogin'],
]);

it('keeps the authenticated workspace hub protected', function (): void {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('keeps protected admin crud pages behind authentication', function (string $routeName): void {
    $this->get(route($routeName))
        ->assertRedirect(route('login'));
})->with([
    'admin sports' => 'admin.sports.index',
    'admin sport create' => 'admin.sports.create',
    'admin amenities' => 'admin.amenities.index',
    'admin amenity create' => 'admin.amenities.create',
    'admin system settings' => 'admin.system_settings.show',
]);

it('renders the authenticated workspace hub for signed-in users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard'),
        );
});
