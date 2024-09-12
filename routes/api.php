<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/password-forgot', [PasswordResetController::class, 'sendResetLink'])->middleware('guest');
Route::post('/auth/password-reset', [PasswordResetController::class, 'resetPassword'])->middleware('guest');
Route::post('/auth', [AuthController::class, 'auth']);
Route::post('/create-user', [UserController::class, 'create']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/auth/new-password', [PasswordResetController::class, 'newPassword']);

    
});

Route::get('/', function () {
    return response()->json([
        'success' => true
    ]);
});
