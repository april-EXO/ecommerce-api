<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;

// Authentication routes (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::put('/user/country', [AuthController::class, 'updateCountry']);
});

// Cart routes (require authentication)
Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);                        // GET /api/cart
    Route::get('/count', [CartController::class, 'count']);                   // GET /api/cart/count
    Route::post('/items', [CartController::class, 'addItem']);                // POST /api/cart/items
    Route::put('/items/{id}', [CartController::class, 'updateItem']);         // PUT /api/cart/items/{id}
    Route::delete('/items/{id}', [CartController::class, 'removeItem']);      // DELETE /api/cart/items/{id}
    Route::delete('/', [CartController::class, 'clear']);                     // DELETE /api/cart
});

// Public routes (no authentication required)
// Categories
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{id}', [CategoryController::class, 'show']);
});

// Products
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);
});

// Legacy route for backward compatibility
Route::get('/countries/{countryCode}/products', [ProductController::class, 'getProductsByCountry']); 