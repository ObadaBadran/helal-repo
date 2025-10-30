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
use App\Http\Controllers\User\EnrollController;
use Illuminate\Support\Facades\Mail;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleEvent;

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

Route::get('/create-meet', function (Request $request) {
    // اقرأ الـ Bearer Token من Header
    $token = $request->bearerToken();
    if (!$token) {
        return response()->json(['message' => 'No token, provide a Bearer token in Authorization header'], 401);
    }

    $client = new GoogleClient();
    $client->setAccessToken(['access_token' => $token]); // ضع التوكن هنا
    $service = new GoogleCalendar($client);

    $event = new GoogleEvent([
        'summary' => 'Test Meeting',
        'description' => 'Meeting created via API',
        'start' => ['dateTime' => now()->addMinutes(5)->toAtomString(), 'timeZone' => 'UTC'],
        'end' => ['dateTime' => now()->addMinutes(65)->toAtomString(), 'timeZone' => 'UTC'],
        'conferenceData' => [
            'createRequest' => [
                'requestId' => 'random-' . uniqid(),
                'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
            ],
        ],
    ]);

    $createdEvent = $service->events->insert('primary', $event, ['conferenceDataVersion' => 1]);

    return response()->json([
        'message' => 'Meet created successfully',
        'meet_link' => $createdEvent->getHangoutLink()
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
Route::middleware('auth:api')->post('/enroll', [EnrollController::class, 'enrollCourse']);
Route::middleware('auth:api')->get('/enrolled_courses', [EnrollController::class, 'showEnrollCourses']);


//Admin

Route::middleware(['auth:api', 'admin'])->group(function () {
    Route::get('users',[AdminContoller::class, 'getUsers']);
});
