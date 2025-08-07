<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApiTokenController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication endpoints
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('token/validate', [AuthController::class, 'validateToken']);
    Route::post('token/refresh', [AuthController::class, 'refreshToken']);
});

// Third-party application authentication
Route::prefix('oauth')->group(function () {
    Route::post('token', [AuthController::class, 'issueToken']);
    Route::post('verify', [AuthController::class, 'verifyToken']);
    Route::post('revoke', [AuthController::class, 'revokeToken']);
});

// Protected routes with Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::delete('auth/account', [AuthController::class, 'deleteAccount']);
    
    // Applications API endpoints
    Route::apiResource('applications', ApplicationController::class);
    Route::post('applications/{application}/regenerate-secret', [ApplicationController::class, 'regenerateSecret']);
    Route::patch('applications/{application}/toggle-status', [ApplicationController::class, 'toggleStatus']);
    
    // API Tokens endpoints
    Route::apiResource('api-tokens', ApiTokenController::class);
    Route::get('applications/{application}/tokens', [ApiTokenController::class, 'index']);
    Route::post('applications/{application}/tokens', [ApiTokenController::class, 'store']);
});

// Custom token authentication middleware for third-party apps
Route::middleware('auth.token')->group(function () {
    Route::get('protected/user', [AuthController::class, 'getAuthenticatedUser']);
    Route::get('protected/profile', [AuthController::class, 'getUserProfile']);
});
