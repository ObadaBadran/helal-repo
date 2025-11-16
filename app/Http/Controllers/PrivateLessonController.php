<?php

namespace App\Http\Controllers;

use App\Models\PrivateLesson;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class PrivateLessonController extends Controller
{
    /**
     * جلب كل الدروس الخصوصية مع Pagination
     */
    public function index(Request $request)
    {
        try {
            $lang = $request->query('lang', 'en');
            $page = (int)$request->query('page', 1);
            $perPage = (int)$request->query('per_page', 10);

            $lessonsPaginated = PrivateLesson::paginate($perPage, ['*'], 'page', $page);

            $data = $lessonsPaginated->map(function ($lesson) use ($lang) {
                return [
                    'id' => $lesson->id,
                    'title' => $lang === 'ar' ? $lesson->title_ar : $lesson->title_en,
                    'description' => $lang === 'ar'
                        ? $lesson->description_ar
                        : $lesson->description_en,
                    'cover_image' => $lesson->cover_image ? asset($lesson->cover_image) : null,
                    'created_at' => $lesson->created_at,
                    'updated_at' => $lesson->updated_at,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Private lessons retrieved successfully',
                'data' => $data,
                'pagination' => [
                    'current_page' => $lessonsPaginated->currentPage(),
                    'last_page' => $lessonsPaginated->lastPage(),
                    'per_page' => $lessonsPaginated->perPage(),
                    'total' => $lessonsPaginated->total(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve private lessons',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض درس خصوصي محدد
     */
    public function show($id)
    {
        try {
            $lesson = PrivateLesson::find($id);

            if (!$lesson) {
                return response()->json([
                    'status' => false,
                    'message' => 'Private lesson not found'
                ], 404);
            }

            if ($lesson->cover_image) {
                $lesson->cover_image = asset($lesson->cover_image);
            }

            return response()->json([
                'status' => true,
                'message' => 'Private lesson retrieved successfully',
                'data' => $lesson
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve private lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إنشاء درس خصوصي
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title_en' => 'required|string|max:255',
                'title_ar' => 'required|string|max:255',
                'description_en' => 'nullable|string',
                'description_ar' => 'nullable|string',
                'cover_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:10240',
            ]);

            if ($request->hasFile('cover_image')) {
                $dir = public_path('private_covers');
                if (!file_exists($dir)) mkdir($dir, 0777, true);

                $imageName = 'private_lesson_' . uniqid() . '.' .
                    $request->file('cover_image')->getClientOriginalExtension();

                $request->file('cover_image')->move($dir, $imageName);

                $data['cover_image'] = 'private_covers/' . $imageName;
            }

            $lesson = PrivateLesson::create($data);

            if ($lesson->cover_image) {
                $lesson->cover_image = asset($lesson->cover_image);
            }

            return response()->json([
                'status' => true,
                'message' => 'Private lesson created successfully',
                'data' => $lesson
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create private lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث درس خصوصي
     */
    public function update(Request $request, $id)
    {
        try {
            $lesson = PrivateLesson::find($id);

            if (!$lesson) {
                return response()->json([
                    'status' => false,
                    'message' => 'Private lesson not found'
                ], 404);
            }

            $data = $request->validate([
                'title_en' => 'sometimes|string|max:255',
                'title_ar' => 'sometimes|string|max:255',
                'description_en' => 'nullable|string',
                'description_ar' => 'nullable|string',
                'cover_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:10240',
            ]);

            if ($request->hasFile('cover_image')) {

                if ($lesson->cover_image && file_exists(public_path($lesson->cover_image))) {
                    unlink(public_path($lesson->cover_image));
                }

                $dir = public_path('private_covers');
                if (!file_exists($dir)) mkdir($dir, 0777, true);

                $imageName = 'private_lesson_' . uniqid() . '.' .
                    $request->file('cover_image')->getClientOriginalExtension();

                $request->file('cover_image')->move($dir, $imageName);

                $data['cover_image'] = 'private_covers/' . $imageName;
            }

            $lesson->update($data);

            if ($lesson->cover_image) {
                $lesson->cover_image = asset($lesson->cover_image);
            }

            return response()->json([
                'status' => true,
                'message' => 'Private lesson updated successfully',
                'data' => $lesson
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update private lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف درس خصوصي
     */
    public function destroy($id)
    {
        try {
            $lesson = PrivateLesson::find($id);

            if (!$lesson) {
                return response()->json([
                    'status' => false,
                    'message' => 'Private lesson not found'
                ], 404);
            }

            if ($lesson->cover_image && file_exists(public_path($lesson->cover_image))) {
                unlink(public_path($lesson->cover_image));
            }

            $lesson->delete();

            return response()->json([
                'status' => true,
                'message' => 'Private lesson deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete private lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
