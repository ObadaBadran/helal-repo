<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enroll;
use Illuminate\Http\Request;
use App\Models\CourseOnline;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Exception;

class CourseOnlineController extends Controller
{
    // إنشاء كورس أونلاين
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'duration' => 'required|integer',
                'price' => 'required|numeric',
                'date' => 'required|date_format:d-m-Y',
                'cover_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:10240',
            ]);

            // تحويل التاريخ إلى صيغة Y-m-d
            $data['date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');

            // حفظ الصورة في storage
            if ($request->hasFile('cover_image')) {
                $data['cover_image'] = $request->file('cover_image')->store('course_covers', 'public');
            }

            $course = CourseOnline::create($data);

            // تعديل رابط الصورة ليصبح رابط كامل
            if ($course->cover_image) {
                $course->cover_image = asset('storage/' . $course->cover_image);
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

    // إضافة رابط الاجتماع وإرسال البريد للمستخدمين
    public function addMeetUrl(Request $request, CourseOnline $course)
{
    try {
        $meetUrl = $request->input('meet_url');
        if (!$meetUrl) {
            $roomName = 'course_' . Str::random(10);
            $meetUrl = "https://meet.jit.si/$roomName";
        }

        $course->update(['meet_url' => $meetUrl]);

        // إرسال البريد للمستخدمين المسجلين والمدفوعين فقط
        $enrolledUsers = $course->enrolls()
            ->where('payment_status', 'paid')
            ->with('user')
            ->get()
            ->pluck('user')
            ->unique('id'); // لتجنب التكرار إذا سجل أكثر من مرة

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
                "Hello {$user->name},\n\nYour online course is ready.\n".
                "Course: {$course->name}\n".
                "Date: {$course->date}\n".
                "Duration: {$course->duration} minutes\n".
                "Price: {$course->price}\n".
                "Join via: {$joinUrl}\n",
                function($message) use ($user, $course) {
                    $message->to($user->email)
                            ->subject("Online Course Ready: {$course->name}");
                }
            );
        }

        // تعديل رابط الصورة ليصبح كامل
        if ($course->cover_image) {
            $course->cover_image = asset('storage/' . $course->cover_image);
        }

        return response()->json([
            'status' => true,
            'message' => 'Meeting link added and emails sent to enrolled users successfully.',
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


    public function update(Request $request, $id)
{
    try {
        $course = CourseOnline::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'duration' => 'sometimes|required|integer',
            'price' => 'sometimes|required|numeric',
            'date' => 'sometimes|required|date_format:d-m-Y',
            'cover_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);

        // تحويل التاريخ إذا تم تمريره
        if (isset($data['date'])) {
            $data['date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');
        }

        // تحديث الصورة إذا تم رفع واحدة جديدة
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('course_covers', 'public');
        }

        $course->update($data);

        // تعديل رابط الصورة ليصبح رابط كامل
        if ($course->cover_image) {
            $course->cover_image = asset('storage/' . $course->cover_image);
        }

        return response()->json([
            'status' => true,
            'message' => 'Course updated successfully.',
            'data' => $course
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Course not found.',
        ], 404);

    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to update course.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    // عرض الكورسات مع Pagination
    public function index(Request $request)
    {
        try {
            $page = (int) $request->query('page', 1);
            $perPage = (int) $request->query('per_page', 10);

            $coursesQuery = CourseOnline::orderBy('id', 'asc');
            $courses = $coursesQuery->paginate($perPage, ['*'], 'page', $page);

            if ($courses->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No courses found.'
                ], 404);
            }

            $data = $courses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'description' => $course->description,
                    'duration' => $course->duration,
                    'price' => $course->price,
                    'date' => $course->date,
                    'cover_image' => $course->cover_image ? asset('storage/' . $course->cover_image) : null,
                    'meet_url' => null,
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


   public function myCourses(Request $request)
{
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

    $data = $enrolls->map(function ($enroll) {
        $course = $enroll->courseOnline;
        if (!$course) return null; 

        return [
            'enroll_id' => $enroll->id,
            'course_id' => $course->id,
            'name' => $course->name,
            'description' => $course->description,
            'duration' => $course->duration,
            'price' => $course->price,
            'date' => $course->date,
            'cover_image' => $course->cover_image ? asset('storage/' . $course->cover_image) : null,
            'meet_url' => $course->meet_url, // رابط الجلسة فقط للمسجلين والمدفوعين
        ];
    })->filter(); // إزالة القيم الفارغة

    return response()->json([
        'status' => true,
        'data' => $data
    ]);
}

    // عرض كورس محدد
    public function show(Request $request, CourseOnline $course)
{
    try {
        $user = auth('api')->user();

        // تعديل رابط الصورة ليصبح كامل
        if ($course->cover_image) {
            $course->cover_image = asset('storage/' . $course->cover_image);
        }

        $meetUrl = null;

        if ($user) {
            // التحقق من التسجيل والمدفوع
            $enroll = $course->enrolls()
                ->where('user_id', $user->id)
                ->where('payment_status', 'paid')
                ->first();

            if ($enroll) {
                $meetUrl = $course->meet_url;
            }
        }

        $responseData = [
            'id' => $course->id,
            'name' => $course->name,
            'description' => $course->description,
            'duration' => $course->duration,
            'price' => $course->price,
            'date' => $course->date,
            'cover_image' => $course->cover_image,
            'meet_url' => $meetUrl, 
        ];

        return response()->json([
            'status' => true,
            'data' => $responseData
        ]);

    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to fetch course.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    // حذف كورس
    public function destroy(CourseOnline $course)
    {
        try {
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
