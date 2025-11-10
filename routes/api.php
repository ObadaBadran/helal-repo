<?php

use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\VideoChatController;
use App\Mail\ContactMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NewsSectionController;
use App\Http\Controllers\AdminContoller;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\User\EnrollController;
use App\Http\Controllers\Admin\CourseOnlineController;
use App\Http\Controllers\Admin\AvailabilityController;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Str;
use App\Models\Meeting;
use App\Models\User;

Route::post('/admin/create-meet', [AdminController::class, 'createMeet'])->middleware('admin');
Route::post('/admin/send-meet-emails/{meeting}', [AdminController::class, 'sendMeetEmails'])->middleware('admin');

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
    Route::post('/broadcast/start', [VideoChatController::class, 'start'])->middleware(['admin']);
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
        Route::delete('/{sectionId}/images', [NewsSectionController::class, 'deleteImages']);

        
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
    Mail::to('haidarahmad421@gmail.com')->send(new ContactMail($data));

    return response()->json([
        'message' => 'Your message has been sent successfully!'
    ]);
});
// Admin
Route::middleware(['auth:api', 'admin'])->group(function() {
    Route::post('/admin/courses/store', [CourseController::class, 'store']);
    Route::post('/admin/courses/update/{id}', [CourseController::class, 'update']);
    Route::delete('/admin/courses/delete/{id}', [CourseController::class, 'destroy']);

    //videos
    Route::post('/admin/videos/store', [VideoController::class, 'store']);
    Route::post('/admin/videos/update/{id}', [VideoController::class, 'update']);
    Route::delete('/admin/videos/delete/{id}', [VideoController::class, 'destroy']);
});

Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);

Route::get('/courses/{course_id}/videos', [VideoController::class, 'index']);
Route::get('/videos/{id}', [VideoController::class, 'show']);

//enroll course
Route::post('/enroll', [EnrollController::class, 'enrollCourse']);
Route::post('/consultation/checkout', [ConsultationController::class, 'createCheckoutSession']);
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
Route::get('/stripe/cancel', [StripeWebhookController::class, 'cancel']);


Route::middleware('auth:api')->get('/enrolled_courses', [EnrollController::class, 'showEnrollCourses']);


//Admin

Route::middleware(['auth:api', 'admin'])->group(function () {
    Route::get('admin/users',[AdminContoller::class, 'getUsers']);
    Route::get('admin/users/by-name-email',[AdminContoller::class, 'getUsersByNameAndEmail']);
    Route::get('admin/consultations', [AdminContoller::class, 'getConsultations']);
    Route::post('admin/consultations/response', [AdminContoller::class, 'addConsultationResponse']);
    Route::get('admin/meetings',[AdminContoller::class, 'getMeetings']);

});

Route::get('/get/personal-information',[AuthController::class,'getUser']);
//courses-online======================================================================
Route::get('/courses-online/get',[CourseOnlineController::class,'index']);
Route::get('/courses-online/show/{course}',[CourseOnlineController::class,'show']);
Route::get('/courses-online/get-my-courses',[CourseOnlineController::class,'myCourses']);


Route::middleware(['auth:api','admin'])->group(function () {
    Route::post('admin/online-course/add',[CourseOnlineController::class,'store']);
    Route::post('admin/online-course/add-meet/{course}',[CourseOnlineController::class,'addMeetUrl']);
    Route::post('admin/online-course/update/{course}',[CourseOnlineController::class,'update']);
    Route::delete('admin/online-course/delete/{course}',[CourseOnlineController::class,'destroy']);
});

Route::middleware(['auth:api','admin'])->group(function () {
    Route::get('admin/availabilities',[AvailabilityController::class,'index']);
    Route::post('admin/availabilities',[AvailabilityController::class,'store']);
    Route::delete('admin/availabilities/{availability}',[AvailabilityController::class,'destroy']);
});

