<?php

namespace App\Http\Controllers;

use App\Models\PrivateLessonInformation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class PrivateLessonInformationController extends Controller
{
    // جلب كل الدروس الخصوصية
    public function index(Request $request)
    {
        try {
            $lang = $request->query('lang', 'en');

            $lessons = PrivateLessonInformation::all()->map(function ($lesson) use ($lang) {
                return [
                    'id' => $lesson->id,
                    'place' => $lang === 'ar' ? ($lesson->place_ar ?? $lesson->place_en) : $lesson->place_en,
                    'price_aed' => $lesson->price_aed,
                    'price_usd' => $lesson->price_usd,
                    'duration' => $lesson->duration,
                    'created_at' => $lesson->created_at,
                    'updated_at' => $lesson->updated_at,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Private lessons retrieved successfully',
                'data' => $lessons
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
    public function show(Request $request, $id)
    {
        try {
            $lang = $request->query('lang', 'en');

            $lesson = PrivateLessonInformation::find($id);

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
                    'place' => $lang === 'ar' ? $lesson->place_ar : $lesson->place_en,
                    'price_aed' => $lesson->price_aed,
                    'price_usd' => $lesson->price_usd,
                    'duration' => $lesson->duration,
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
                'place_en' => 'required|string|max:255',
                'place_ar' => 'required|string|max:255',
                'price_aed' => 'required|numeric|min:0',
                'price_usd' => 'required|numeric|min:0',
                'duration' => 'required|integer|min:1|max:100',

            ]);

            $lesson = PrivateLessonInformation::create($validated);

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
            $lesson = PrivateLessonInformation::find($id);

            if (!$lesson) {
                return response()->json([
                    'status' => false,
                    'message' => 'Private lesson not found'
                ], 404);
            }

            $validated = $request->validate([
                'place_en' => 'required|string|max:255',
                'place_ar' => 'required|string|max:255',
                'price_aed' => 'required|numeric|min:0',
                'price_usd' => 'required|numeric|min:0',
                'duration' => 'sometimes|integer|max:100',
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
            $lesson = PrivateLessonInformation::find($id);

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
