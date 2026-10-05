<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SubscriberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/by-ids', [ProductController::class, 'byIds']);
Route::get('/products/{idOrSlug}', [ProductController::class, 'show']);
Route::get('/products/{id}/related', [ProductController::class, 'related']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/blogs', [BlogController::class, 'index']);
Route::get('/blogs/{slug}', [BlogController::class, 'show']);
Route::get('/settings', [SettingController::class, 'index']);

Route::get('/hero-slides', [ContentController::class, 'heroSlides']);
Route::get('/testimonials', [ContentController::class, 'testimonials']);
Route::get('/features', [ContentController::class, 'features']);
Route::get('/instagram-posts', [ContentController::class, 'instagramPosts']);
Route::get('/lookbook', [ContentController::class, 'lookbook']);

Route::post('/subscribe', [SubscriberController::class, 'store'])->middleware('throttle:10,1');
Route::post('/checkout/quote', [OrderController::class, 'quote'])->middleware('throttle:60,1');
Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:10,1');
Route::get('/orders/track', [OrderController::class, 'track'])->middleware('throttle:20,1');

// Customer accounts
Route::post('/auth/register', [AccountController::class, 'register'])->middleware('throttle:10,1');
Route::post('/auth/login', [AccountController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AccountController::class, 'logout']);
    Route::get('/account', [AccountController::class, 'me']);
    Route::put('/account', [AccountController::class, 'update']);
    Route::put('/account/password', [AccountController::class, 'changePassword'])->middleware('throttle:10,1');
    Route::get('/account/orders', [AccountController::class, 'orders']);
});
