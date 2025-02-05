<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostsController as PostController;

// Public Routes
Route::post('/signup-auth-code', [AuthController::class, 'sendSignupAuthCode']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/validate-auth-code', [AuthController::class, 'validateAuthCode']);

Route::prefix('posts')->group(function () {
    Route::post('/posts/list', [PostController::class, 'index']);
    Route::post('/posts/show', [PostController::class, 'show']);
});

// Protected Routes (Requires Authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('posts')->group(function () {
        Route::post('/posts/create', [PostController::class, 'store']);
        Route::post('/posts/update', [PostController::class, 'update']);
        Route::post('/posts/delete', [PostController::class, 'destroy']);
    });
});
