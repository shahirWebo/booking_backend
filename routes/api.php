<?php

use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RequestOtpController;
use App\Http\Controllers\Api\V1\Auth\VerifyOtpController;
use Illuminate\Support\Facades\Route;

/*
 * Public REST endpoints live under the API middleware group's `/api` prefix.
 * Add compatible endpoints to the current version group; preserve older groups
 * when a future breaking API version is introduced.
 */
Route::middleware('throttle:api')->prefix('v1')->as('api.v1.')->group(function (): void {
    Route::get('/', fn () => response()->noContent())->name('index');

    Route::post('auth/otp-requests', RequestOtpController::class)->name('auth.otp_requests.store');
    Route::post('auth/otp-verifications', VerifyOtpController::class)->name('auth.otp_verifications.store');
    Route::delete('auth/session', LogoutController::class)->middleware('auth:sanctum')->name('auth.session.destroy');
});
