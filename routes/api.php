<?php

use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

Route::middleware('web')->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/add', [CartController::class, 'add']);
    Route::put('/items/{productId}', [CartController::class, 'update']);
    Route::delete('/items/{productId}', [CartController::class, 'remove']);
});

Route::middleware(['web', 'auth'])->post('/checkout', [CheckoutController::class, 'store']);

Route::middleware(['web', 'auth'])->prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::get('/{orderNumber}', [OrderController::class, 'show']);
});

Route::get('/banners', [BannerController::class, 'index']);

Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/products', [AdminProductController::class, 'index']);
    Route::post('/products', [AdminProductController::class, 'store']);
    Route::put('/products/{id}', [AdminProductController::class, 'update']);
    Route::delete('/products/{id}', [AdminProductController::class, 'destroy']);
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::put('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
    Route::get('/banners', [BannerController::class, 'adminIndex']);
    Route::post('/banners', [BannerController::class, 'store']);
    Route::put('/banners/{id}', [BannerController::class, 'update']);
    Route::delete('/banners/{id}', [BannerController::class, 'destroy']);
});
