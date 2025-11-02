<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Video;
use App\PaginationTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewCourseMail;
use App\Models\Enroll;

class CourseController extends Controller
{

    use PaginationTrait;


    public function index(Request $request)
    {
        try {
            $lang = $request->query('lang', 'en'); 
            $user = auth('api')->user();

            $courses = Course::select(
                'id',
                'title_en', 'title_ar',
                'subTitle_en', 'subTitle_ar',
                'description_en', 'description_ar',
                'price_usd', 'price_aed',
                'reviews',
                'image',
            )->get()->map(function ($course) use ($lang, $user) {

                $isEnrolled = false;
                if ($user) {
                    $isEnrolled = Enroll::where('user_id', $user->id)
                        ->where('course_id', $course->id)
                        ->where('payment_status', 'paid')
                        ->exists();
                }

                return [
                    'id' => $course->id,
                    'title' => $lang === 'ar' ? $course->title_ar : $course->title_en,
                    'subTitle' => $lang === 'ar' ? $course->subTitle_ar : $course->subTitle_en,
                    'description' => $lang === 'ar' ? $course->description_ar : $course->description_en,
                    'price_aed' => $course->price_aed,
                    'price_usd' => $course->price_usd,
                    'reviews' => $course->reviews,
                    'image' => $course->image ? asset($course->image) : null,
                    'is_enroll' => $isEnrolled, 
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Courses retrieved successfully',
                'courses' => $courses
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve courses',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        $user = auth('api')->user();
        if(!$user) return response()->json(['message' => 'Unauthorized'], 401);
        
        try {
            $validatedData = $request->validate([
                'title_en' => 'required|string|max:255',
                'title_ar' => 'required|string|max:255',
                'subTitle_en' => 'nullable|string|max:255',
                'subTitle_ar' => 'nullable|string|max:255',
                'description_en' => 'required|string',
                'description_ar' => 'required|string',
                'price_aed' => 'required|numeric|min:0',
                'price_usd' => 'required|numeric|min:0',
                'reviews' => 'required|integer|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('course_images', 'public');
                $validatedData['image'] = '/storage/' . $path;
            }

            $course = Course::create($validatedData);

            $users = User::where('role', 'user')->get();

            foreach ($users as $user) {
                $courseUrl = $request->input('course_url');
                Mail::to($user->email)->send(new NewCourseMail($course, $user, $courseUrl));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Course created successfully',
                'course' => $course
            ], 201);



        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create course',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function show(Request $request, $id)
    {
        $user = auth('api')->user();
        if(!$user) return response()->json(['message' => 'Unauthorized'], 401);

        try {
            $lang = $request->query('lang', 'en');
            $course = Course::findOrFail($id);

            $courseData = [
                'id' => $course->id,
                'title' => $lang === 'ar' ? $course->title_ar : $course->title_en,
                'subTitle' => $lang === 'ar' ? $course->subTitle_ar : $course->subTitle_en,
                'description' => $lang === 'ar' ? $course->description_ar : $course->description_en,
                'price_aed' => $course->price_aed,
                'price_usd' => $course->price_usd,
                'reviews' => $course->reviews,
                'image' => $course->image ? asset($course->image) : null,
            ];

            $videosQuery = Video::where('course_id', $course->id)
                ->select('id', $lang === 'ar' ? 'title_ar as title' : 'title_en as title', 'path');

            $videosPaginated = $this->paginateResponse($request, $videosQuery, 'Videos', function ($video) {
                return [
                    'id' => $video->id,
                    'title' => $video->title,
                    'video_url' => $video->path,
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Course retrieved successfully',
                'course' => $courseData,
                'videos' => $videosPaginated,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course not found'
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve course',
                'error' => $e->getMessage()
            ], 500);
        }
    }


   public function update(Request $request, $id)
    {
        try {
            $course = Course::findOrFail($id);

            $validatedData = $request->validate([
                'title_en' => 'nullable|string|max:255',
                'title_ar' => 'nullable|string|max:255',
                'subTitle_en' => 'nullable|string|max:255',
                'subTitle_ar' => 'nullable|string|max:255',
                'description_en' => 'nullable|string',
                'description_ar' => 'nullable|string',
                'price_aed' => 'nullable|numeric|min:0',
                'price_usd' => 'nullable|numeric|min:0',
                'reviews' => 'nullable|integer|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($request->hasFile('image')) {
                // حذف الصورة القديمة إن وجدت
                if ($course->image && file_exists(public_path('storage/' . $course->image))) {
                    unlink(public_path('storage/' . $course->image));
                }

                // رفع الصورة الجديدة
                $path = $request->file('image')->store('course_images', 'public');
                $validatedData['image'] = '/storage/' . $path ;
            }

            $course->update($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'Course updated successfully',
                'course' => $course
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course not found'
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update course',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function destroy($id)
    {
        try {
            $course = Course::findOrFail($id);
            $course->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Course deleted successfully'
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course not found'
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete course',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
