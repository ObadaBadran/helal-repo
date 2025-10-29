<?php

use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\VideoChatController;
use App\Mail\ContactMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NewsSectionController;
use Illuminate\Support\Facades\Mail;

//Auth***************************************************************************************
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/update-profile-image', [AuthController::class, 'updateProfileImage']);
});
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
//videoCall*********************************************************************************
Route::middleware('auth:api')->group(function () {
    Route::post('/call-user', [VideoChatController::class, 'callUser']);
    Route::post('/accept-call', [VideoChatController::class, 'acceptCall']);

    // بث جماعي
    Route::post('/broadcast/start', [VideoChatController::class, 'start']);
    Route::post('/broadcast/signal', [VideoChatController::class, 'signal']);
});

//news section =========================================================================
Route::prefix('news-sections')->group(function () {
    Route::get('/', [NewsSectionController::class, 'index']);
    Route::get('/{id}', [NewsSectionController::class, 'show']);


    Route::middleware(['auth:api', 'admin'])->group(function () {
        Route::post('/', [NewsSectionController::class, 'store']);
        Route::post('/{id}', [NewsSectionController::class, 'update']);
        Route::delete('/{id}', [NewsSectionController::class, 'destroy']);
    });
});
//email**************************************************************************
Route::post('/contact/send', function(Request $request) {
    $data = $request->validate([
        'full_name' => 'required|string|max:255',
        'email' => 'required|email',
        'subject' => 'required|string|max:255',
        'message' => 'required|string',
    ]);

    // إرسال البريد إلى المدير
    Mail::to('obadabadran382@gmail.com')->send(new ContactMail($data));

    return response()->json([
        'message' => 'Your message has been sent successfully!'
    ]);
});
// Admin
Route::middleware(['auth:api', 'admin'])->group(function() {
    Route::post('/admin/courses/store', [CourseController::class, 'store']);
    Route::post('/admin/courses/update/{id}', [CourseController::class, 'update']);
    Route::delete('/admin/courses/delete/{id}', [CourseController::class, 'destroy']);
});

Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);

