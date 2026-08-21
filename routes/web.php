<?php

use App\Http\Controllers\Admin\AmenityManagementController;
use App\Http\Controllers\Admin\SportManagementController;
use App\Http\Controllers\Admin\SystemSettingManagementController;
use App\Http\Controllers\Api\V1\Customer\CustomerProfileController;
use App\Http\Controllers\Vendor\VendorOnboardingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('customer')->name('customer.')->group(function () {
    Route::inertia('/', 'customer/Home')->name('home');
});

Route::middleware(['auth', 'active-user'])
    ->prefix('customer')
    ->name('customer.')
    ->group(function (): void {
        Route::get('profile', [CustomerProfileController::class, 'show'])->name('profile.show');
        Route::put('profile', [CustomerProfileController::class, 'update'])->name('profile.update');
        Route::post('profile/deletion-request', [CustomerProfileController::class, 'requestDeletion'])->name('profile.deletion-request');
    });

Route::middleware(['auth', 'active-user'])
    ->get('profile', fn () => to_route('customer.profile.show'));

Route::prefix('vendor')->name('vendor.')->group(function () {
    Route::inertia('/', 'vendor/Home')->name('home');
    Route::get('login', fn (Request $request) => Inertia::render('auth/Login', [
        'canResetPassword' => Features::enabled(Features::resetPasswords()),
        'status' => $request->session()->get('status'),
        'intendedUrl' => route('vendor.onboarding.show'),
    ]))->middleware('guest')->name('login');
});

Route::middleware(['auth', 'active-user'])
    ->prefix('vendor')
    ->name('vendor.')
    ->group(function (): void {
        Route::get('onboarding', [VendorOnboardingController::class, 'show'])->name('onboarding.show');
        Route::put('onboarding/{vendor}/business-details', [VendorOnboardingController::class, 'updateBusinessDetails'])
            ->name('onboarding.business-details.update');
        Route::put('onboarding/{vendor}/primary-contact', [VendorOnboardingController::class, 'updatePrimaryContact'])
            ->name('onboarding.primary-contact.update');
    });

Route::prefix('admin')->name('admin.')->group(function () {
    Route::inertia('/', 'admin/Home')->name('home');
    Route::get('login', fn (Request $request) => Inertia::render('auth/AdminLogin', [
        'canResetPassword' => Features::enabled(Features::resetPasswords()),
        'status' => $request->session()->get('status'),
    ]))->middleware('guest')->name('login');
});

Route::middleware(['auth', 'active-user', 'permission:manage_sports'])
    ->prefix('admin/operations')
    ->name('admin.sports.')
    ->group(function (): void {
        Route::get('sports', [SportManagementController::class, 'index'])->name('index');
        Route::get('sports/create', [SportManagementController::class, 'create'])->name('create');
        Route::post('sports', [SportManagementController::class, 'store'])->name('store');
        Route::get('sports/{sport}/edit', [SportManagementController::class, 'edit'])->name('edit');
        Route::put('sports/{sport}', [SportManagementController::class, 'update'])->name('update');
        Route::delete('sports/{sport}', [SportManagementController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'active-user', 'permission:manage_amenities'])
    ->prefix('admin/operations')
    ->name('admin.amenities.')
    ->group(function (): void {
        Route::get('amenities', [AmenityManagementController::class, 'index'])->name('index');
        Route::get('amenities/create', [AmenityManagementController::class, 'create'])->name('create');
        Route::post('amenities', [AmenityManagementController::class, 'store'])->name('store');
        Route::get('amenities/{amenity}/edit', [AmenityManagementController::class, 'edit'])->name('edit');
        Route::put('amenities/{amenity}', [AmenityManagementController::class, 'update'])->name('update');
        Route::delete('amenities/{amenity}', [AmenityManagementController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'active-user', 'permission:manage_system_settings'])
    ->prefix('admin/governance')
    ->name('admin.system_settings.')
    ->group(function (): void {
        Route::get('system-settings', [SystemSettingManagementController::class, 'show'])->name('show');
        Route::put('system-settings', [SystemSettingManagementController::class, 'update'])->name('update');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
