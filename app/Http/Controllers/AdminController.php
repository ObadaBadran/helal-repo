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
        $validated = $request->validate([
            'summary' => 'required|string|max:255',
            'start_time' => 'required|date',
            'duration' => 'required|integer|min:1',
        ]);

        // توليد اسم قناة فريد لـ Agora
        $channelName = 'course_' . Str::random(10);

        $meeting = Meeting::create([
            'summary' => $validated['summary'],
            'start_time' => $validated['start_time'],
            'duration' => $validated['duration'],
            'meet_url' => $channelName, // نخزن هنا اسم القناة لاستخدامه في التوكن لاحقاً
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Meeting created successfully',
            'meeting' => $meeting
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Validation error',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error creating meeting',
            'error' => $e->getMessage()
        ], 500);
    }
}
  public function sendMeetEmails(Request $request, Meeting $meeting)
{
    try {
       
        $adminUser = auth('api')->user();

       
        $validated = $request->validate([
            'user_ids' => 'sometimes|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        // 3. تحديد الفئة المستهدفة: إما IDs محددة أو كل الطلاب
        $users = empty($validated['user_ids'])
            ? User::where('role', 'user')->get()
            : User::whereIn('id', $validated['user_ids'])->get();

        if ($users->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'No users found.'], 404);
        }

        
        $studentBaseUrl = config('services.meet_url.web'); 
        $adminBaseUrl = config('services.meet_url.dash');

        $studentJoinUrl = rtrim($studentBaseUrl, '/') . '/' . $meeting->meet_url;
        $adminJoinUrl = rtrim($adminBaseUrl, '/') . '/' . $meeting->meet_url;

        // 5. إرسال الإيميلات للطلاب
        foreach ($users as $user) {
            Mail::send('emails.meeting_scheduled', [
                'url' => $studentJoinUrl,
                'meeting' => $meeting,
                'user' => $user
            ], function ($message) use ($user) {
                $message->to($user->email)->subject('موعد محاضرة مباشرة جديدة');
            });
        }

        // 6. إرسال إشعار للأدمن برابط لوحة التحكم الخاصة به
        if (config('services.admin.address')) {
            Mail::send('emails.admin_meeting_created', [
               'meeting' => $meeting,
               'url'     => $adminJoinUrl, // تمرير الرابط ليتناسب مع الـ Blade
               'users'   => $users,
            ], function ($message) {
                $message->to(config('services.admin.address'))->subject('تم إنشاء الاجتماع بنجاح');
            });
        }

        return response()->json([
            'status' => true,
            'message' => 'Emails sent successfully',
            'details' => [
                'sent_to' => $users->count(),
                'channel_name' => $meeting->meet_url,
                'student_url' => $studentJoinUrl
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to send emails',
            'error' => $e->getMessage()
        ], 500);
    }
}



}
