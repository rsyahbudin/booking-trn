<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Menu;
use App\Models\SeatingSpot;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Public Booking Route
Route::view('/booking', 'booking')->name('booking.form');

Route::get('dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

// Admin Routes - Accessible by all authenticated users (admin & karyawan)
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard - accessible by all roles
    Route::view('/', 'admin.index')->name('dashboard');
    
    // Daftar Pesanan - accessible by all roles
    Route::view('/bookings/orders', 'admin.bookings.orders')->name('bookings.orders');
});

// Admin-only Routes - Only accessible by admin role
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Categories
    Route::view('/categories', 'admin.categories.index')->name('categories.index');
    Route::view('/categories/create', 'admin.categories.create')->name('categories.create');
    Route::get('/categories/{category}/edit', fn(Category $category) => view('admin.categories.edit', compact('category')))->name('categories.edit');

    // Menus
    Route::view('/menus', 'admin.menus.index')->name('menus.index');
    Route::view('/menus/create', 'admin.menus.create')->name('menus.create');
    Route::get('/menus/{menu}/edit', fn(Menu $menu) => view('admin.menus.edit', compact('menu')))->name('menus.edit');

    // Bookings (admin only - full access)
    Route::view('/bookings', 'admin.bookings.index')->name('bookings.index');
    Route::get('/bookings/{booking}', fn(Booking $booking) => view('admin.bookings.show', compact('booking')))->name('bookings.show');
    Route::get('/bookings/{booking}/edit', fn(Booking $booking) => view('admin.bookings.edit', compact('booking')))->name('bookings.edit');

    // Seating Spots
    Route::view('/seating-spots', 'admin.seating-spots.index')->name('seating-spots.index');
    Route::view('/seating-spots/create', 'admin.seating-spots.create')->name('seating-spots.create');
    Route::get('/seating-spots/{spot}/edit', fn(SeatingSpot $spot) => view('admin.seating-spots.edit', compact('spot')))->name('seating-spots.edit');
});
