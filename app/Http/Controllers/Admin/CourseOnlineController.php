<?php

namespace App\Http\Controllers\Admin;

use App\HandlesAppointmentTimesTrait;
use App\Http\Controllers\Controller;
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
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'name_ar' => 'required|string|max:255',
                'description_ar' => 'required|string',
                // 'duration' => 'required|integer',
                'price' => 'required|numeric',
                'date' => 'required|date_format:d-m-Y',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'cover_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:10240',
            ]);

            if (!$this->checkAppointmentConflict($request->date, $request->start_time, $request->end_time)) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is another appointment at this time.'
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
            $meetUrl = $request->input('meet_url');
            if (!$meetUrl) {
                $roomName = 'course_' . Str::random(10);
                $meetUrl = "https://meet.jit.si/$roomName";
            }

            $course->update(['meet_url' => $meetUrl]);

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

            foreach ($enrolledUsers as $user) {
                $roomId = basename($course->meet_url);
                $joinUrl = "http://localhost:5173/Helal-Aljaberi/course/{$roomId}";

                Mail::raw(
                    "Hello {$user->name},\n\nYour online course is ready.\n" .
                    "Course: {$course->name}\n" .
                    "Date: {$course->appointment->date}\n" .
                    "Start time: {$course->appointment->start_time}\n" .
                    "End time: {$course->appointment->end_time}\n" .
                    // "Duration: {$course->duration} minutes\n" .
                    "Price: {$course->price}\n" .
                    "Join via: {$joinUrl}\n",
                    function ($message) use ($user, $course) {
                        $message->to($user->email)
                            ->subject("Online Course Ready: {$course->name}");
                    }
                );
            }

            if ($course->cover_image) {
                $course->cover_image = asset($course->cover_image);
            }

            return response()->json([
                'status' => true,
                'message' => 'Meeting link added and emails sent successfully.',
                'data' => $course,
                'meet_url' => $meetUrl
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to add meeting link or send emails.',
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
                'name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'name_ar' => 'required|string|max:255',
                'description_ar' => 'required|string',
                // 'duration' => 'sometimes|required|integer',
                'price' => 'sometimes|required|numeric',
                'date' => 'sometimes|date_format:d-m-Y',
                'start_time' => 'required_with:end_time|date_format:H:i',
                'end_time' => 'required_with:start_time|date_format:H:i|after:start_time',
                'cover_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:10240',
            ]);

            if (isset($data['date'])) {
                $data['date'] = Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');

                $appointment = $course->appointment;

                if (!$this->checkAppointmentConflict($request->date,
                    $request->start_time ?? $appointment->start_time,
                    $request->end_time ?? $appointment->start_time, $appointment->id)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'There is another appointment at this time.'
                    ], 400);
                }

                $course->appointment->update($data);
            }

            if ($request->hasFile('cover_image')) {
                // حذف القديمة
                if ($course->cover_image && file_exists(public_path($course->cover_image))) {
                    unlink(public_path($course->cover_image));
                }

                $dir = public_path('course_covers');
                if (!file_exists($dir)) mkdir($dir, 0777, true);

                $imageName = 'course_' . uniqid() . '.' . $request->file('cover_image')->getClientOriginalExtension();
                $request->file('cover_image')->move($dir, $imageName);
                $data['cover_image'] = 'course_covers/' . $imageName;
            }

            $course->update($data);

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

            $courses = CourseOnline::orderBy('id', 'asc')->paginate($perPage, ['*'], 'page', $page);

            if ($courses->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No courses found.'
                ], 404);
            }

            $data = $courses->map(function ($course) use ($lang) {
                return [
                    'id' => $course->id,
                    'name' => $lang === 'ar' ? $course->name_ar : $course->name,
                    'description' => $lang === 'ar' ? $course->description_ar : $course->description,
                    // 'duration' => $course->duration,
                    'price' => $course->price,
                    // 'date' => $course->date,
                    'cover_image' => $course->cover_image ? asset($course->cover_image) : null,
                    'meet_url' => $course->meet_url,
                    'appointment' => $course->appointment,
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

            return [
                'enroll_id' => $enroll->id,
                'course_id' => $course->id,
                'name' => $lang === 'ar' ? $course->name_ar : $course->name,
                'description' => $lang === 'ar' ? $course->description_ar : $course->description,
                // 'duration' => $course->duration,
                'price' => $course->price,
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
                    'name' => $lang === 'ar' ? $course->name_ar : $course->name,
                    'description' => $lang === 'ar' ? $course->description_ar : $course->description,
                    // 'duration' => $course->duration,
                    'price' => $course->price,
                    // 'date' => $course->date,
                    'cover_image' => $course->cover_image,
                    'meet_url' => $meetUrl,
                    'appointment' => $course->appointment,
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
            if ($course->cover_image && file_exists(public_path($course->cover_image))) {
                unlink(public_path($course->cover_image));
            }

            $course->appointment->delete();

            $course->delete();

            return response()->json([
                'status' => true,
                'message' => 'Course deleted successfully.'
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
