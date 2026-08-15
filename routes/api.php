<?php

use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RequestOtpController;
use App\Http\Controllers\Api\V1\Auth\ShowCurrentUserController;
use App\Http\Controllers\Api\V1\Auth\VerifyOtpController;
use Illuminate\Support\Facades\Route;

/*
 * Public REST endpoints live under the API middleware group's `/api` prefix.
 * Add compatible endpoints to the current version group; preserve older groups
 * when a future breaking API version is introduced.
 */
Route::middleware('throttle:api')->prefix('v1')->as('api.v1.')->group(function (): void {
    Route::get('/', fn () => response()->noContent())->name('index');

    Route::prefix('auth')->as('auth.')->group(function (): void {
        Route::post('otp-requests', RequestOtpController::class)->name('otp_requests.store');
        Route::post('otp-verifications', VerifyOtpController::class)->name('otp_verifications.store');
        Route::get('user', ShowCurrentUserController::class)->middleware(['auth:sanctum', 'active-user'])->name('user.show');
        Route::delete('session', LogoutController::class)->middleware(['auth:sanctum', 'active-user'])->name('session.destroy');
    });
});
