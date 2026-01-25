<?php

namespace App\Http\Controllers\Admin;

use App\HandlesAppointmentTimesTrait;
use App\Http\Controllers\Controller;
use App\Mail\CourseReadyMail;
use App\Models\Appointment;
use App\Models\Enroll;
use Illuminate\Http\Request;
use App\Models\CourseOnline;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Exception;
use Carbon\Carbon;

class CourseOnlineController extends Controller
{
    use HandlesAppointmentTimesTrait;

    /**
     * إنشاء كورس أونلاين
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name_en' => 'required|string|max:255',
                'description_en' => 'required|string',
                'name_ar' => 'required|string|max:255',
                'description_ar' => 'required|string',
                // 'duration' => 'required|integer',
                'price_aed' => 'required|numeric|min:0',
                'price_usd' => 'required|numeric|min:0',
                'date' => 'required|date_format:d-m-Y',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'cover_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:10240',
            ]);

            if (!$this->checkAppointmentConflict($request->date, $request->start_time, $request->end_time)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to create course.',
                    'error' => 'There is another appointment at this time.'
                ], 400);
            }

            // تحويل التاريخ إلى Y-m-d
            $data['date'] = Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');

            // تخزين الصورة في public/storage/course_covers
            if ($request->hasFile('cover_image')) {
                $dir = public_path('course_covers');
                if (!file_exists($dir)) mkdir($dir, 0777, true);

                $imageName = 'course_' . uniqid() . '.' . $request->file('cover_image')->getClientOriginalExtension();
                $request->file('cover_image')->move($dir, $imageName);
                $data['cover_image'] = 'course_covers/' . $imageName;
            }

            $appointment = Appointment::create($data);

            $course = $appointment->courseOnline()->create($data);

            // تحويل الرابط ليكون كامل
            if ($course->cover_image) {
                $course->cover_image = asset($course->cover_image);
            }

            return response()->json([
                'status' => true,
                'message' => 'Course created successfully.',
                'data' => $course
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create course.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إضافة رابط الاجتماع وإرسال البريد
     */
   public function addMeetUrl(Request $request, CourseOnline $course)
{
    try {
        
        $channelName = $request->input('meet_url');
        if (!$channelName) {
            $channelName = 'course_live_' . Str::random(10);
        }

        $enrolledUsers = $course->enrolls()
            ->where('payment_status', 'paid')
            ->with('user')
            ->get()
            ->pluck('user')
            ->unique('id');

        if ($enrolledUsers->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No enrolled users found to send emails.'
            ], 404);
        }

        
        $course->update([
            'meet_url' => $channelName, 
           
        ]);

       
        $studentBaseUrl = config('services.meet_url.web'); // http://localhost:5173/Helal-Aljaberi/meet/
        $fullJoinUrl = rtrim($studentBaseUrl, '/') . '/' . $channelName;

        foreach ($enrolledUsers as $user) {
           
            Mail::to($user->email)->send(new CourseReadyMail($user, $course, $fullJoinUrl));
        }

        // تحسين عرض البيانات في الرد
        if ($course->cover_image) {
            $course->cover_image = asset($course->cover_image);
        }

        return response()->json([
            'status' => true,
            'message' => 'Agora channel created and emails sent successfully.',
            'data' => [
                'course_id' => $course->id,
                'channel_name' => $channelName,
                'join_url' => $fullJoinUrl,
                'active' => $course->active
            ]
        ]);

    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to process Agora meeting.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * تحديث كورس
     */
    public function update(Request $request, $id)
{
    try {
        $course = CourseOnline::findOrFail($id);

        $data = $request->validate([
            'name_en' => 'sometimes|string|max:255',
            'description_en' => 'sometimes|string',
            'name_ar' => 'sometimes|string|max:255',
            'description_ar' => 'sometimes|string',
            'price_aed' => 'sometimes|numeric|min:0',
            'price_usd' => 'sometimes|numeric|min:0',
            'date' => 'sometimes|date_format:d-m-Y',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'cover_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);

        // معالجة تحديث الموعد
        if (isset($data['date']) || isset($data['start_time']) || isset($data['end_time'])) {
            $appointment = $course->appointment;

            // تحضير بيانات الموعد للتحديث
            $appointmentData = [];

            if (isset($data['date'])) {
                $appointmentData['date'] = Carbon::createFromFormat('d-m-Y', $data['date'])->format('Y-m-d');
            }

            if (isset($data['start_time'])) {
                $appointmentData['start_time'] = $data['start_time'];
            }

            if (isset($data['end_time'])) {
                $appointmentData['end_time'] = $data['end_time'];
            }

            // التحقق من التعارض فقط إذا كان هناك تغيير في الوقت
            if (!empty($appointmentData)) {
                $checkDate = $appointmentData['date'] ?? $appointment->date->format('Y-m-d');
                $checkStartTime = $appointmentData['start_time'] ?? $appointment->start_time;
                $checkEndTime = $appointmentData['end_time'] ?? $appointment->end_time;

                if (!$this->checkAppointmentConflict($checkDate, $checkStartTime, $checkEndTime, $appointment->id)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Failed to update course.',
                        'error' => 'There is another appointment at this time.'
                    ], 400);
                }

                // تحديث الموعد
                $appointment->update($appointmentData);

                // إعادة تعيين رابط الاجتماع عند تغيير الموعد
                $data['meet_url'] = null;
            }

            // إزالة بيانات الموعد من $data حتى لا يتم تحديثها في جدول الكورس
            unset($data['date'], $data['start_time'], $data['end_time']);
        }

        // معالجة تحديث الصورة
        if ($request->hasFile('cover_image')) {
            // حذف الصورة القديمة
            if ($course->cover_image && file_exists(public_path($course->cover_image))) {
                unlink(public_path($course->cover_image));
            }

            $dir = public_path('course_covers');
            if (!file_exists($dir)) mkdir($dir, 0777, true);

            $imageName = 'course_' . uniqid() . '.' . $request->file('cover_image')->getClientOriginalExtension();
            $request->file('cover_image')->move($dir, $imageName);
            $data['cover_image'] = 'course_covers/' . $imageName;
        }

        // تحديث بيانات الكورس
        $course->update($data);

        // تحميل العلاقة المحدثة
        $course->load('appointment');

        if ($course->cover_image) {
            $course->cover_image = asset($course->cover_image);
        }

        return response()->json([
            'status' => true,
            'message' => 'Course updated successfully.',
            'data' => $course
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to update course.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * عرض الكورسات مع Pagination
     */
    public function index(Request $request)
    {
        try {
            $lang = $request->query('lang', 'en');
            $page = (int)$request->query('page', 1);
            $perPage = (int)$request->query('per_page', 10);
            $active = (bool)$request->query('active');

            $courses = CourseOnline::with('appointment');

            if($request->has('active')){
                if($active === true)
                    $courses = $courses->where('active', true);
                if($active === false)
                    $courses = $courses->where('active', false);
            }

            $courses = $courses->orderBy('id', 'asc')
                ->paginate($perPage, ['*'], 'page', $page);

           
            $data = $courses->map(function ($course) use ($lang) {
                return [
                    'id' => $course->id,
                    'name' => $lang === 'ar' ? $course->name_ar : $course->name_en,
                    'description' => $lang === 'ar' ? $course->description_ar : $course->description_en,
                    // 'duration' => $course->duration,
                    'price_aed' => $course->price_aed,
                    'price_usd' => $course->price_usd,
                    // 'date' => $course->date,
                    'cover_image' => $course->cover_image ? asset($course->cover_image) : null,
                    //'meet_url' => $course->meet_url,
                    'appointment' => $course->appointment,
                    //'active' => $course->active,
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $courses->currentPage(),
                    'last_page' => $courses->lastPage(),
                    'per_page' => $courses->perPage(),
                    'total' => $courses->total(),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch courses.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * كورسات المستخدم
     */
    public function myCourses(Request $request)
    {
        $lang = $request->query('lang', 'en');

        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $enrolls = $user->enrolls()
            ->with('courseOnline')
            ->whereNotNull('course_online_id')
            ->get();

        $data = $enrolls->map(function ($enroll) use ($lang) {
            $course = $enroll->courseOnline;
            if (!$course) return null;

            //$joinUrl = $course->meet_url ? config('services.meet_url.web') . basename($course->meet_url) : null;

            return [
                'enroll_id' => $enroll->id,
                'course_id' => $course->id,
                'name' => $lang === 'ar' ? $course->name_ar : $course->name_en,
                'description' => $lang === 'ar' ? $course->description_ar : $course->description_en,
                // 'duration' => $course->duration,
                'price_aed' => $course->price_aed,
                'price_usd' => $course->price_usd,
                // 'date' => $course->date,
                'cover_image' => $course->cover_image ? asset($course->cover_image) : null,
                'meet_url' => $course->meet_url,
                'appointment' => $course->appointment,
            ];
        })->filter();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * عرض كورس محدد
     */
    public function show(Request $request, CourseOnline $course)
    {
        try {
            $lang = $request->query('lang', 'en');
            $user = auth('api')->user();

            if ($course->cover_image) {
                $course->cover_image = asset($course->cover_image);
            }

            $meetUrl = null;
            if ($user) {
                $enroll = $course->enrolls()
                    ->where('user_id', $user->id)
                    ->where('payment_status', 'paid')
                    ->first();

                if ($enroll) {
                    $meetUrl = $course->meet_url;
                }
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'id' => $course->id,
                    'name' => $lang === 'ar' ? $course->name_ar : $course->name_en,
                    'description' => $lang === 'ar' ? $course->description_ar : $course->description_en,
                    // 'duration' => $course->duration,
                    'price_aed' => $course->price_aed,
                    'price_usd' => $course->price_usd,
                    // 'date' => $course->date,
                    'cover_image' => $course->cover_image,
                  //  'meet_url' => $meetUrl,
                    'appointment' => $course->appointment,
                  //  'active' => $course->active,
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch course.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف كورس
     */
   public function destroy(CourseOnline $course)
{
    try {
       
        Enroll::where('course_online_id', $course->id)->delete();

        
        if ($course->cover_image && file_exists(public_path($course->cover_image))) {
            unlink(public_path($course->cover_image));
        }

       
        if ($course->appointment) {
            $course->appointment->delete();
        }

        
        $course->delete();

        return response()->json([
            'status' => true,
            'message' => 'Course and related enrollments deleted successfully.'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to delete course.',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
