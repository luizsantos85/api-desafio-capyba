<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::post('/auth/password-forgot', [PasswordResetController::class, 'sendResetLink'])->middleware('guest');
Route::post('/auth/password-reset', [PasswordResetController::class, 'resetPassword'])->middleware('guest');
Route::post('/auth', [AuthController::class, 'auth']);
Route::post('/create-user', [UserController::class, 'create']);
Route::get('/user/verify/{id}/{hash}', [UserController::class, 'verifyEmailUser'])->name('email.verify');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('user')->group(function () {
        Route::post('/new-password', [PasswordResetController::class, 'newPassword']);
        Route::post('/{id}/verify/resend/', [UserController::class, 'resendVerificationEmail']);
        Route::put('/update', [UserController::class, 'update']);
        Route::get('/profile', [UserController::class, 'show']);
    });

    Route::prefix('posts')->group(function () {
        Route::get('/', [PostController::class, 'index']);
        Route::post('/store', [PostController::class, 'store']);
        Route::get('/show/{id}', [PostController::class, 'show']);

        //Apenas usuarios com confirmação de email poderam editar/deletar posts
        Route::middleware('verified')->group(function () {
            Route::put('/update/{id}', [PostController::class, 'update']);
            Route::delete('/delete/{id}', [PostController::class, 'destroy']);
        });
    });


});

//rota teste api
Route::get('/', function () {
    return response()->json([
        'success' => true
    ]);
});
