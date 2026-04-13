<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Shop/Index');
})->name('home');

Route::get('/welcome', function () {
    return Inertia::render('Welcome');
})->name('welcome');

Route::get('/shop', function () {
    return redirect()->route('home');
})->name('shop.index');

Route::get('/cart', function () {
    return Inertia::render('Shop/Cart');
})->name('shop.cart');

Route::get('/checkout', function () {
    return Inertia::render('Shop/Checkout');
})->middleware(['auth', 'verified'])->name('shop.checkout');

Route::get('/orders', function () {
    return Inertia::render('Shop/Orders');
})->middleware(['auth', 'verified'])->name('shop.orders');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    Route::get('/products', function () {
        return Inertia::render('Admin/Products');
    })->name('admin.products');

    Route::get('/banners', function () {
        return Inertia::render('Admin/Banners');
    })->name('admin.banners');

    Route::get('/orders', function () {
        return Inertia::render('Admin/Orders');
    })->name('admin.orders');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
