<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('customer')->name('customer.')->group(function () {
    Route::inertia('/', 'customer/Home')->name('home');
});

Route::prefix('vendor')->name('vendor.')->group(function () {
    Route::inertia('/', 'vendor/Home')->name('home');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::inertia('/', 'admin/Home')->name('home');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
