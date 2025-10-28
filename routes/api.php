<?php

use App\Http\Controllers\VideoChatController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

});


Route::middleware('auth:api')->group(function () {
    Route::post('/call-user', [VideoChatController::class, 'callUser']);
    Route::post('/accept-call', [VideoChatController::class, 'acceptCall']);

    // بث جماعي
    Route::post('/broadcast/start', [VideoChatController::class, 'start']);
    Route::post('/broadcast/signal', [VideoChatController::class, 'signal']);
});
