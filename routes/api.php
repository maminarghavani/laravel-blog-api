<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogCategoryController;
use App\Http\Controllers\Api\BlogPostController;
use Illuminate\Support\Facades\Route;

// Authentication
Route::post('/register-request', [AuthController::class, 'registerRequest']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password-request', [AuthController::class, 'forgotPasswordRequest']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
// Posts
Route::apiResource('posts', BlogPostController::class)->only(['index', 'show']);
// Categories
Route::apiResource('categories', BlogCategoryController::class)->only(['index', 'show']);

Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    // Posts
    Route::apiResource('posts', BlogPostController::class)->only(['update', 'store', 'destroy']);
    // Categories
    Route::apiResource('categories', BlogCategoryController::class)->only(['update', 'store', 'destroy']);
});
