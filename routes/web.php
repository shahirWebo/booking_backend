<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('customer')->name('customer.')->group(function () {
    Route::inertia('/', 'customer/Home')->name('home');
});

Route::prefix('vendor')->name('vendor.')->group(function () {
    Route::inertia('/', 'vendor/Home')->name('home');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::inertia('/', 'admin/Home')->name('home');
    Route::get('login', fn (Request $request) => Inertia::render('auth/AdminLogin', [
        'canResetPassword' => Features::enabled(Features::resetPasswords()),
        'status' => $request->session()->get('status'),
    ]))->middleware('guest')->name('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
