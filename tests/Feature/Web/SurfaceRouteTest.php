<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

it('registers product surface routes with dedicated URI and name prefixes', function (): void {
    $routes = [
        'home' => ['GET', '/', 'Welcome'],
        'customer.home' => ['GET', 'customer', 'customer/Home'],
        'vendor.home' => ['GET', 'vendor', 'vendor/Home'],
        'admin.home' => ['GET', 'admin', 'admin/Home'],
        'admin.sports.index' => ['GET', 'admin/operations/sports', 'admin/Sports'],
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
    ['admin.home', 'admin/Home'],
    ['admin.sports.index', 'admin/Sports'],
    ['admin.login', 'auth/AdminLogin'],
]);

it('keeps the authenticated workspace hub protected', function (): void {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('renders the authenticated workspace hub for signed-in users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard'),
        );
});
