<?php

use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RequestOtpController;
use App\Http\Controllers\Api\V1\Auth\ShowCurrentUserController;
use App\Http\Controllers\Api\V1\Auth\VerifyOtpController;
use Illuminate\Support\Facades\Route;

it('groups authentication endpoints under the auth URI and name prefixes', function (): void {
    $routes = [
        'api.v1.auth.otp_requests.store' => ['POST', 'api/v1/auth/otp-requests', RequestOtpController::class],
        'api.v1.auth.otp_verifications.store' => ['POST', 'api/v1/auth/otp-verifications', VerifyOtpController::class],
        'api.v1.auth.user.show' => ['GET', 'api/v1/auth/user', ShowCurrentUserController::class],
        'api.v1.auth.session.destroy' => ['DELETE', 'api/v1/auth/session', LogoutController::class],
    ];

    foreach ($routes as $name => [$method, $uri, $controller]) {
        $route = Route::getRoutes()->getByName($name);

        expect($route)
            ->not->toBeNull()
            ->and($route->methods())->toContain($method)
            ->and($route->uri())->toBe($uri)
            ->and($route->getActionName())->toBe($controller);
    }
});
