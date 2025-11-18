<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Course;
use App\Models\CourseOnline;
use App\Models\User;
use App\Models\Meeting;
use App\PaginationTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;


class AdminController extends Controller
{
    use PaginationTrait;

    public function getUsers(Request $request)
    {
        try {

            $lang = $request->query('lang', 'en');


            $page = (int)$request->query('page', 1);
            $perPage = (int)$request->query('per_page', (int)$request->query('sizer', 10));


            $usersQuery = User::where('role', 'user')->orderBy('id', 'asc');
            $users = $usersQuery->paginate($perPage, ['*'], 'page', $page);

            if ($request->has('course_id')) {
                $courseId = $request->query('course_id');
                $course = Course::find($courseId);
                if (!$course) return response()->json(['message' => 'Course not found'], 404);
                $usersQuery->whereHas('enrolls', function ($query) use ($courseId) {
                    $query->where('course_id', $courseId)
                        ->where('payment_status', 'paid');
                });
            } else if ($request->has('course_online_id')) {
                $courseId = $request->query('course_online_id');
                $course = CourseOnline::find($courseId);
                if (!$course) return response()->json(['message' => 'Course not found'], 404);
                $usersQuery->whereHas('enrolls', function ($query) use ($courseId) {
                    $query->where('course_online_id', $courseId)
                        ->where('payment_status', 'paid');
                });
            }

            $users = $usersQuery->paginate($perPage, ['*'], 'page', $page);


            $data = $users->map(function ($user) use ($lang) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ];
            });

            if ($data->isEmpty()) {
                return response()->json(['message' => 'No users found.'], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),

                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Something went wrong.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getMeetings()
    {
        $meetings = Meeting::all();
        if ($meetings->isEmpty()) {
            return response()->json(['message' => 'No meetings found.'], 404);
        }

        return response()->json([
            'message' => 'Meetings have been successfully retrieved ✅',
            'data' => $meetings
        ], 200);
    }

    public function addConsultationResponse(Request $request)
    {

        try {
            $validatedData = $request->validate([
                'consultation_id' => 'required|integer|exists:consultations,id',
                'meet_url' => 'required|url',
                'date' => 'required|date_format:Y-m-d',
                'time' => 'required|date_format:H:i',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'errors' => $e->getMessage()
            ], 422);
        }

        $consultation = Consultation::find($validatedData['consultation_id']);
        if (!$consultation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Consultation not found.'
            ], 404);
        }

        $consultation->update([
            'meet_url' => $validatedData['meet_url'],
            'consultation_date' => $validatedData['date'],
            'consultation_time' => $validatedData['time'],
            'is_done' => true,
        ]);

        $locale = app()->getLocale();
        $isArabic = $locale === 'ar';

        try {
            Mail::send('emails.consultation_meet', [
                'consultation' => $consultation,
                'locale' => $locale,
            ], function ($message) use ($consultation, $isArabic) {
                $message->to($consultation->email)
                    ->subject($isArabic
                        ? 'تفاصيل استشارتك الخاصة'
                        : 'Your Private Consultation Details');
            });
        } catch (Exception $e) {
            Log::error('Failed to send consultation email', [
                'consultation_id' => $consultation->id,
                'user_email' => $consultation->email,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => $isArabic
                ? 'تم إرسال تفاصيل الاستشارة إلى البريد الإلكتروني للمستخدم.'
                : 'Consultation details sent to user email.',
        ]);
    }

    public function getConsultations(Request $request)
    {
        try {
            $page = (int)$request->query('page', 1);
            $perPage = (int)$request->query('per_page', (int)$request->query('size', 10));

            $consultationsQuery = Consultation::where('payment_status', 'paid')->orderBy('id', 'asc');

            $consultations = $consultationsQuery->paginate($perPage, ['*'], 'page', $page);

            $data = $consultations->map(function ($consultation) {
                return [
                    'id' => $consultation->id,
                    'name' => $consultation->name ?? null,
                    'email' => $consultation->email ?? null,
                    'phone' => $consultation->phone ?? null,
                    'meet_url' => $consultation->meet_url ?? null,
                    'is_done' => $consultation->is_done,
                    'information' => $consultation->information,
                    'appointment' => $consultation->appointment,
                    'currency' => $consultation->currency,
                    'created_at' => $consultation->created_at->toDateTimeString(),
                ];
            });

            if ($data->isEmpty()) {
                return response()->json(['message' => 'No consultations found.'], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $consultations->currentPage(),
                    'last_page' => $consultations->lastPage(),
                    'per_page' => $consultations->perPage(),
                    'total' => $consultations->total(),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Something went wrong.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

   public function getUsersByNameAndEmail(Request $request)
    {
    try {
        // أخذ المدخلات من query parameters بدلاً من request body
        $page = (int)$request->query('page', 1);
        $perPage = (int)$request->query('per_page', 10);
        $search = trim($request->query('search', ''));
        $courseId = $request->query('course_id');

        // الاستعلام الأساسي (بدون تقييد الدور لتسهيل التجربة)
        $usersQuery = User::query();

        // إذا تم إدخال نص بحث
        if (!empty($search)) {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // تصفية حسب course_id إن وجد
        if ($courseId) {
            $usersQuery->whereHas('enrolls', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            });
        }

        // تنفيذ الاستعلام مع الترتيب والصفحات
        $users = $usersQuery->orderBy('id', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        // تحويل البيانات بشكل منسق
        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'profile_image' => $user->profile_image ? asset($user->profile_image) : null,
                'created_at' => $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        if ($data->isEmpty()) {
           return response()->json([
            'status' => true,
            'message' => $data->isEmpty() ? 'No users found.' : 'Users retrieved successfully.',
            'data' => $data,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
        }

        return response()->json([
            'status' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'error' => 'Something went wrong.',
            'message' => $e->getMessage(),
        ], 500);
    }
}

    public function createMeet(Request $request)
    {
        try {
            // ✅ التحقق من البيانات
            $validated = $request->validate([
                'summary' => 'nullable|string|max:255',
                'start_time' => 'nullable|date',
                'duration' => 'nullable|integer|min:1|max:480', // 8 ساعات كحد أقصى
            ]);

            $summary = $validated['summary'] ?? 'Meeting';
            $startTime = $validated['start_time'] ?? now();
            $duration = $validated['duration'] ?? 60;

            // ✅ إنشاء رابط الاجتماع العشوائي
            $roomName = 'meeting_' . Str::random(10);
            $meetUrl = "https://meet.jit.si/{$roomName}";

            // ✅ حفظ الاجتماع
            $meeting = Meeting::create([
                'summary' => $summary,
                'start_time' => $startTime,
                'duration' => $duration,
                'meet_url' => $meetUrl,
            ]);

            // ✅ حذف الاجتماعات القديمة والإبقاء على آخر 10
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
                'status' => true,
                'message' => 'Meeting created successfully',
                'meeting' => $meeting
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while creating the meeting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

  public function sendMeetEmails(Request $request, Meeting $meeting)
{
    try {
        // تحقق من وجود الاجتماع
        if (!$meeting) {
            return response()->json([
                'status' => false,
                'message' => 'Meeting not found'
            ], 404);
        }

        // الحصول على المستخدم الحالي (الأدمن)
        $adminUser = auth('api')->user();
        if (!$adminUser) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // التحقق من البيانات
        $validated = $request->validate([
            'user_ids' => 'sometimes|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        // إذا لم يُحدد الأدمن المستخدمين، اجلب كل المستخدمين العاديين
        $users = empty($validated['user_ids']) 
            ? User::where('role', 'user')->get() 
            : User::whereIn('id', $validated['user_ids'])->get();

        if ($users->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No users found to send email.'
            ], 404);
        }

        // روابط مباشرة داخل الكنترولر
        $studentBaseUrl = config('services.meet_url.web');
        $adminBaseUrl = config('services.meet_url.dash');
        $roomId = str_replace('https://meet.jit.si/', '', $meeting->meet_url);

        $studentJoinUrl = $studentBaseUrl . $roomId;
        $adminJoinUrl = $adminBaseUrl . $roomId;

        // إرسال البريد لكل مستخدم عادي
        foreach ($users as $user) {
            Mail::raw(
                "Hello {$user->name},\n\n" .
                "A new meeting has been scheduled.\n" .
                "Topic: {$meeting->summary}\n" .
                "Start time: {$meeting->start_time}\n" .
                "Duration: {$meeting->duration} minutes\n" .
                "Join via: {$studentJoinUrl}\n\n" .
                "Regards,\n\n" .
                "-----------------------------\n\n" .
                "مرحباً {$user->name},\n" .
                "تم تحديد اجتماع جديد.\n" .
                "الموضوع: {$meeting->summary}\n" .
                "وقت البدء: {$meeting->start_time}\n" .
                "المدة: {$meeting->duration} دقيقة\n" .
                "رابط الانضمام: {$studentJoinUrl}\n\n" .
                "تحياتنا.",
                function ($message) use ($user, $meeting) {
                    $message->to($user->email)
                        ->subject("Meeting Invitation: {$meeting->summary}");
                }
            );
        }

        // إرسال البريد للأدمن
        Mail::raw(
            "Hello {$adminUser->name},\n\n" .
            "You have created a new meeting.\n" .
            "Topic: {$meeting->summary}\n" .
            "Start time: {$meeting->start_time}\n" .
            "Duration: {$meeting->duration} minutes\n" .
            "Admin Join Link: {$adminJoinUrl}\n\n" .
            "Invited Users:\n" .
            $users->pluck('name')->implode("\n") . "\n\n" .
            "Regards,\n\n" .
            "-----------------------------\n\n" .
            "مرحباً {$adminUser->name},\n\n" .
            "لقد قمت بإنشاء اجتماع جديد.\n" .
            "الموضوع: {$meeting->summary}\n" .
            "وقت البدء: {$meeting->start_time}\n" .
            "المدة: {$meeting->duration} دقيقة\n" .
            "رابط الانضمام للأدمن: {$adminJoinUrl}\n\n" .
            "المستخدمين المدعوين:\n" .
            $users->pluck('name')->implode("\n") . "\n\n" .
            "تحياتنا.",
            function ($message) use ($adminUser, $meeting) {
                $message->to($adminUser->email)
                    ->subject("Meeting Created: {$meeting->summary}");
            }
        );

        return response()->json([
            'status' => true,
            'message' => 'Emails sent successfully',
            'sent_to_users' => $users->count(),
            'sent_to_admin' => 1,
            'student_join_url' => $studentJoinUrl,
            'admin_join_url' => $adminJoinUrl,
            'invited_users' => $users->pluck('name')
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Validation failed',
            'errors' => $e->getMessage()
        ], 422);
    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred while sending emails',
            'error' => $e->getMessage()
        ], 500);
    }
}



}
