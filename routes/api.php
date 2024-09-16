<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/password-forgot', [PasswordResetController::class, 'sendResetLink'])->middleware('guest');
Route::post('/auth/password-reset', [PasswordResetController::class, 'resetPassword'])->middleware('guest');
Route::post('/auth', [AuthController::class, 'auth']);
Route::post('/create-user', [UserController::class, 'create']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/user/new-password', [PasswordResetController::class, 'newPassword']);
    Route::put('/user/update', [UserController::class, 'update']);
    Route::get('/user', [UserController::class, 'show']);

    Route::prefix('posts')->group(function () {
        Route::get('/', [PostController::class, 'index']);
        Route::post('/store', [PostController::class, 'store']);
        Route::get('/show/{id}', [PostController::class, 'show']);

        //Apenas usuarios com confirmação de email poderam editar/deletar posts
        Route::put('/update/{id}', [PostController::class, 'update']);
        Route::delete('/delete/{id}', [PostController::class, 'destroy']);
    });


});

Route::get('/', function () {
    return response()->json([
        'success' => true
    ]);
});
