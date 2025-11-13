<?php

namespace App\Http\Controllers;

use App\Models\PrivateLesson;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class PrivateLessonController extends Controller
{
    // جلب كل الدروس الخصوصية
   public function index(Request $request)
{
    try {
        $lang = $request->query('lang', 'en');
        $page = (int)$request->query('page', 1);
        $perPage = (int)$request->query('per_page', 10);

        // استخدام paginate بدلاً من all
        $lessonsPaginated = PrivateLesson::paginate($perPage, ['*'], 'page', $page);

        // تحويل البيانات
        $data = $lessonsPaginated->map(function ($lesson) use ($lang) {
            return [
                'id' => $lesson->id,
                'title' => $lang === 'ar' ? $lesson->title_ar : $lesson->title_en,
                'description' => $lang === 'ar' ? $lesson->description_ar : $lesson->description_en,
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
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to retrieve private lessons',
            'error' => $e->getMessage()
        ], 500);
    }
}

    // عرض درس خصوصي محدد
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

            return response()->json([
                'status' => true,
                'message' => 'Private lesson retrieved successfully',
                'data' => [
                    'id' => $lesson->id,
                    'title_en' => $lesson->title_en,
                    'title_ar' => $lesson->title_ar,
                    'description_en' => $lesson->description_en,
                    'description_ar' => $lesson->description_ar,
                    'created_at' => $lesson->created_at,
                    'updated_at' => $lesson->updated_at,
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve private lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // إنشاء درس خصوصي جديد
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title_en' => 'required|string|max:255',
                'title_ar' => 'required|string|max:255',
                'description_en' => 'nullable|string',
                'description_ar' => 'nullable|string',
            ]);

            $lesson = PrivateLesson::create($validated);

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

    // تحديث درس خصوصي
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

            $validated = $request->validate([
                'title_en' => 'sometimes|string|max:255',
                'title_ar' => 'sometimes|string|max:255',
                'description_en' => 'nullable|string',
                'description_ar' => 'nullable|string',
            ]);

            $lesson->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Private lesson updated successfully',
                'data' => $lesson
            ], 200);

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

    // حذف درس خصوصي
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

            $lesson->delete();

            return response()->json([
                'status' => true,
                'message' => 'Private lesson deleted successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete private lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
