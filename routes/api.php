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
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Meeting;
use App\Models\User;
/*
Route::get('/auth/google', function () {
    $client = new GoogleClient();
    $client->setClientId(env('GOOGLE_CLIENT_ID'));
    $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
    $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
    $client->addScope('https://www.googleapis.com/auth/calendar');

    $authUrl = $client->createAuthUrl();
    return redirect($authUrl); // يعيدك إلى Google لتسجيل الدخول
});

Route::get('/auth/google/callback', function (Request $request) {
    $client = new GoogleClient();
    $client->setClientId(env('GOOGLE_CLIENT_ID'));
    $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
    $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));

    $client->authenticate($request->code);
    $token = $client->getAccessToken();

    session(['google_token' => $token]);

    return response()->json([
        'message' => 'Google OAuth successful',
        'access_token' => $token
    ]);
});
/*

Route::post('/create-meet', function (Request $request) {
    // جلب التوكين من الـ Bearer
    $token = $request->bearerToken();
    if (!$token) {
        return response()->json(['message' => 'No Bearer token found'], 401);
    }

    // ضبط Google Client
    $client = new GoogleClient();
    $client->setAccessToken([
        "access_token" => $token,
        "token_type" => "Bearer"
    ]);

    $service = new GoogleCalendar($client);

    // قراءة المدخلات
    $duration = $request->duration ?? 60;
    $startTime = $request->start_time;

    if ($startTime) {
        $start = Carbon::parse($startTime);
    } else {
        // اجتماع يبدأ بعد دقيقة بشكل فوري
        $start = Carbon::now()->addMinute();
    }

    $end = (clone $start)->addMinutes($duration);

    // إعداد الحدث
    $event = new GoogleEvent([
        'summary' => $request->summary ?? 'Meeting',
        'description' => $request->description ?? 'Meeting created via API',
        'start' => [
            'dateTime' => $start->toAtomString(),
            'timeZone' => 'UTC'
        ],
        'end' => [
            'dateTime' => $end->toAtomString(),
            'timeZone' => 'UTC'
        ],
        'conferenceData' => [
            'createRequest' => [
                'requestId'            => uniqid(),
                'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
            ]
        ]
    ]);

    // إرسال الطلب إلى Google Calendar
    $createdEvent = $service->events->insert('primary', $event, [
        'conferenceDataVersion' => 1
    ]);

    return response()->json([
        'message' => 'Meet created successfully ✅',
        'meet_link' => $createdEvent->getHangoutLink(),
        'start_time' => $start->toDateTimeString(),
        'end_time' => $end->toDateTimeString()
    ]);
});

Route::post('/create-meet', function (Request $request) {
    // مسار JSON Key
    $serviceAccountPath = storage_path('app/helal-meet-integration-84a7d3fb246b.json');

    if (!file_exists($serviceAccountPath)) {
        return response()->json(['error' => 'Service account file not found'], 500);
    }

    // إنشاء Google Client
    $client = new GoogleClient();
    $client->setAuthConfig($serviceAccountPath);
    $client->setScopes([GoogleCalendar::CALENDAR]);

    // مهم: البريد الذي يملك صلاحية إنشاء Google Meet
    $client->setSubject('obadab2001@gmail.com');

    $service = new GoogleCalendar($client);

    // بيانات الاجتماع من البوستمان
    $summary = $request->summary ?? 'Meeting';
    $description = $request->description ?? 'Created via API';
    $duration = $request->duration ?? 60;
    $startTime = $request->start_time ? Carbon::parse($request->start_time) : Carbon::now()->addMinute();
    $endTime = (clone $startTime)->addMinutes($duration);

    // إنشاء الحدث مع Google Meet
    $event = new GoogleEvent([
        'summary' => $summary,
        'description' => $description,
        'start' => [
            'dateTime' => $startTime->toAtomString(),
            'timeZone' => 'UTC'
        ],
        'end' => [
            'dateTime' => $endTime->toAtomString(),
            'timeZone' => 'UTC'
        ],
        'conferenceData' => [
            'createRequest' => [
                'requestId' => uniqid(),
                'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
            ]
        ]
    ]);

    try {
        $createdEvent = $service->events->insert('primary', $event, [
            'conferenceDataVersion' => 1
        ]);

        return response()->json([
            'message' => 'Event with Meet created successfully ✅',
            'meet_link' => $createdEvent->getHangoutLink(),
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
        ]);
    } catch (\Google\Service\Exception $e) {
        return response()->json([
            'error' => 'Google API error',
            'message' => $e->getMessage()
        ], 500);
    }
});*/

Route::post('/create-meet', function(Request $request) {
    $summary = $request->summary ?? 'Meeting';
    $startTime = $request->start_time ?? now();
    $duration = $request->duration ?? 60; // دقائق

    // إنشاء اسم فريد للغرفة
    $roomName = 'meeting_' . Str::random(10);
    $meetUrl = "https://meet.jit.si/$roomName";

    // حفظ الاجتماع في DB
    $meeting = Meeting::create([
        'summary' => $summary,
        'start_time' => $startTime,
        'duration' => $duration,
        'meet_url' => $meetUrl,
    ]);

    return response()->json([
        'message' => 'Meeting created successfully',
        'meet_url' => $meetUrl,
        'summary' => $summary,
        'start_time' => $startTime,
        'duration' => $duration,
    ]);
});

Route::post('/send-meet-emails/{meeting}', function(Request $request, Meeting $meeting) {

    // جلب جميع المستخدمين العاديين
    $users = User::where('role', 'user')->get();

    foreach ($users as $user) {
        Mail::raw(
            "Hello {$user->name},\n\nA new meeting has been scheduled.\n".
            "Topic: {$meeting->summary}\n".
            "Start time: {$meeting->start_time}\n".
            "Duration: {$meeting->duration} minutes\n".
            "Join via: {$meeting->meet_url}\n\n".
            "Regards\n\n".
            "-----------------------------\n\n".
            "مرحباً {$user->name},\n\nتم تحديد اجتماع جديد.\n".
            "الموضوع: {$meeting->summary}\n".
            "وقت البدء: {$meeting->start_time}\n".
            "المدة: {$meeting->duration} دقيقة\n".
            "رابط الانضمام: {$meeting->meet_url}\n\n".
            "مع تحياتنا",
            function($message) use ($user, $meeting) {
                $message->to($user->email)
                        ->subject("New Meeting / اجتماع جديد: {$meeting->summary}");
            }
        );
    }

    return response()->json([
        'message' => 'Emails sent to all users successfully ✅'
    ]);
});
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
});

Route::get('/get/personal-information',[AuthController::class,'getUser']);
