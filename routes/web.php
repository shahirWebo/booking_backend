<?php

use App\Http\Controllers\Admin\AmenityManagementController;
use App\Http\Controllers\Admin\SportManagementController;
use App\Http\Controllers\Admin\SystemSettingManagementController;
use App\Http\Controllers\Admin\VendorOperationsController;
use App\Http\Controllers\Admin\VendorReviewController;
use App\Http\Controllers\Api\V1\Customer\CustomerProfileController;
use App\Http\Controllers\Vendor\VendorLocationController;
use App\Http\Controllers\Vendor\VendorOnboardingController;
use App\Http\Controllers\Vendor\VendorTurfController;
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
        'surfaceTitle' => 'Vendor access',
        'surfaceDescription' => 'Use your mobile OTP to continue vendor onboarding, upload compliance evidence, and track review decisions.',
    ]))->middleware('guest')->name('login');
});

Route::middleware(['auth', 'active-user'])
    ->prefix('vendor')
    ->name('vendor.')
    ->group(function (): void {
        Route::get('onboarding', [VendorOnboardingController::class, 'show'])->name('onboarding.show');
        Route::put('onboarding/{vendor}/business-details', [VendorOnboardingController::class, 'updateBusinessDetails'])->name('onboarding.business-details.update');
        Route::put('onboarding/{vendor}/primary-contact', [VendorOnboardingController::class, 'updatePrimaryContact'])->name('onboarding.primary-contact.update');
        Route::put('onboarding/{vendor}/gst-details', [VendorOnboardingController::class, 'updateGstDetails'])->name('onboarding.gst-details.update');
        Route::post('onboarding/{vendor}/kyc-documents', [VendorOnboardingController::class, 'uploadKycDocument'])->name('onboarding.kyc-documents.store');
        Route::post('onboarding/{vendor}/bank-accounts', [VendorOnboardingController::class, 'storeBankAccount'])->name('onboarding.bank-accounts.store');
        Route::post('onboarding/{vendor}/submit', [VendorOnboardingController::class, 'submit'])->name('onboarding.submit');
        Route::post('onboarding/{vendor}/prepare-resubmission', [VendorOnboardingController::class, 'prepareResubmission'])->name('onboarding.resubmission.prepare');

        Route::get('locations', [VendorLocationController::class, 'index'])->name('locations.index');
        Route::get('locations/create', [VendorLocationController::class, 'create'])->name('locations.create');
        Route::post('locations', [VendorLocationController::class, 'store'])->name('locations.store');
        Route::get('locations/{location}/edit', [VendorLocationController::class, 'edit'])->name('locations.edit');
        Route::put('locations/{location}', [VendorLocationController::class, 'update'])->name('locations.update');
        Route::post('locations/{location}/status', [VendorLocationController::class, 'updateStatus'])->name('locations.status.update');

        Route::get('locations/{location}/turfs', [VendorTurfController::class, 'index'])->name('locations.turfs.index');
        Route::get('locations/{location}/turfs/create', [VendorTurfController::class, 'create'])->name('locations.turfs.create');
        Route::post('locations/{location}/turfs', [VendorTurfController::class, 'store'])->name('locations.turfs.store');
        Route::get('turfs/{turf}/edit', [VendorTurfController::class, 'edit'])->name('turfs.edit');
        Route::put('turfs/{turf}', [VendorTurfController::class, 'update'])->name('turfs.update');
        Route::post('turfs/{turf}/status', [VendorTurfController::class, 'updateStatus'])->name('turfs.status.update');
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

Route::middleware(['auth', 'active-user', 'permission:review_vendors'])
    ->prefix('admin/operations/vendors')
    ->name('admin.vendor_reviews.')
    ->group(function (): void {
        Route::get('/', [VendorReviewController::class, 'index'])->name('index');
        Route::get('{vendor}', [VendorReviewController::class, 'show'])->name('show');
        Route::post('{vendor}/approve', [VendorReviewController::class, 'approve'])->name('approve');
        Route::post('{vendor}/reject', [VendorReviewController::class, 'reject'])->name('reject');
    });

Route::middleware(['auth', 'active-user', 'permission:view_vendors'])
    ->prefix('admin/operations/vendors')
    ->name('admin.vendor_operations.')
    ->group(function (): void {
        Route::get('lifecycle', [VendorOperationsController::class, 'index'])->name('index');
        Route::get('lifecycle/{vendor}', [VendorOperationsController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'active-user', 'permission:suspend_vendors'])
    ->prefix('admin/operations/vendors')
    ->name('admin.vendor_operations.')
    ->group(function (): void {
        Route::post('lifecycle/{vendor}/suspend', [VendorOperationsController::class, 'suspend'])->name('suspend');
    });

Route::middleware(['auth', 'active-user', 'permission:reactivate_vendors'])
    ->prefix('admin/operations/vendors')
    ->name('admin.vendor_operations.')
    ->group(function (): void {
        Route::post('lifecycle/{vendor}/reactivate', [VendorOperationsController::class, 'reactivate'])->name('reactivate');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
