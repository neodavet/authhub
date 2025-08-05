<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Authentication routes (Laravel UI required - install with: composer require laravel/ui)
// Auth::routes();

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Protected routes
Route::middleware(['auth'])->group(function () {
    // Applications CRUD
    Route::resource('applications', ApplicationController::class);
    Route::post('applications/{application}/regenerate-secret', [ApplicationController::class, 'regenerateSecret'])
        ->name('applications.regenerate-secret');
    Route::patch('applications/{application}/toggle-status', [ApplicationController::class, 'toggleStatus'])
        ->name('applications.toggle-status');
    
    // API Tokens CRUD
    Route::resource('api-tokens', ApiTokenController::class)->except(['create', 'edit']);
    Route::get('applications/{application}/tokens', [ApiTokenController::class, 'index'])
        ->name('applications.tokens.index');
    Route::post('applications/{application}/tokens', [ApiTokenController::class, 'store'])
        ->name('applications.tokens.store');
});
