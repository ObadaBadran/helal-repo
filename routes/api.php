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
use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Str;
use App\Models\Meeting;
use App\Models\User;

Route::post('/create-meet', function(Request $request) {
    $summary = $request->summary ?? 'Meeting';
    $startTime = $request->start_time ?? now();
    $duration = $request->duration ?? 60; 

   
    $roomName = 'meeting_' . Str::random(10);
    $meetUrl = "https://meet.jit.si/$roomName";

   
    $meeting = Meeting::create([
        'summary' => $summary,
        'start_time' => $startTime,
        'duration' => $duration,
        'meet_url' => $meetUrl,
    ]);

  
    $totalMeetings = Meeting::count();
    if ($totalMeetings > 10) {
        $toDelete = Meeting::orderBy('created_at', 'asc')
            ->take($totalMeetings - 10)
            ->get();

        foreach ($toDelete as $oldMeeting) {
            $oldMeeting->delete();
        }
    }

    return response()->json([
        'message' => 'Meeting created successfully',
        'meet_url' => $meetUrl,
        'summary' => $summary,
        'start_time' => $startTime,
        'duration' => $duration,
    ]);
});

Route::post('/admin/send-meet-emails/{meeting}', function(Request $request, Meeting $meeting) {

    $userIds = $request->input('user_ids');

    if ($userIds && is_array($userIds)) {
        $users = User::where('role', 'user')
            ->whereIn('id', $userIds)
            ->get();
    } else {
        $users = User::where('role', 'user')->get();
    }

    if ($users->isEmpty()) {
        return response()->json([
            "status" => false,
            "message" => "No users found to send email."
        ], 404);
    }

    $roomId = basename($meeting->meet_url);
    $joinUrl = "http://localhost:5173/Helal-Aljaberi/{$roomId}"; 

    foreach ($users as $user) {

        Mail::raw(
            "Hello {$user->name},\n\nA new meeting has been scheduled.\n".
            "Topic: {$meeting->summary}\n".
            "Start time: {$meeting->start_time}\n".
            "Duration: {$meeting->duration} minutes\n".
            "Join via: {$joinUrl}\n\n".
            "Regards\n".
            "-----------------------------\n\n".
            "مرحباً {$user->name},\n\nتم تحديد اجتماع جديد.\n".
            "الموضوع: {$meeting->summary}\n".
            "وقت البدء: {$meeting->start_time}\n".
            "المدة: {$meeting->duration} دقيقة\n".
            "رابط الانضمام: {$joinUrl}\n\n".
            "مع تحياتنا",
            function($message) use ($user, $meeting) {
                $message->to($user->email)
                    ->subject("New Meeting / اجتماع جديد: {$meeting->summary}");
            }
        );
    }

    return response()->json([
        'status' => true,
        'message' => 'Emails sent successfully',
        'sent_to_count' => $users->count()
    ]);
})->middleware('admin');

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
    Route::get('admin/consultations', [AdminContoller::class, 'getConsultations']);
    Route::post('admin/consultations/response', [AdminContoller::class, 'addConsultationResponse']);
    Route::get('admin/meetings',[AdminContoller::class, 'getMeetings']);
    Route::get('admin/meetings',[AdminContoller::class, 'getMeetings']);
    Route::get('admin/users/search', [AdminContoller::class, 'searchUser']);

});

Route::get('/get/personal-information',[AuthController::class,'getUser']);
