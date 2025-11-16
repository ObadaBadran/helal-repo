<?php

namespace App\Http\Controllers;

use App\Models\PrivateLessonInformation;
use App\Models\PrivateLesson;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class PrivateLessonInformationController extends Controller
{
    // جلب كل الدروس الخصوصية
    public function index(Request $request, $private_lesson_id)
    {
        try {
            $lang = $request->query('lang', 'en');

            $lessons = PrivateLessonInformation::where('private_lesson_id', $private_lesson_id)
                ->whereNull('appointment_id')
                ->with(['lesson', 'appointment'])
                ->get()
                ->map(function ($lesson) use ($lang) {
                    return [
                        'id' => $lesson->id,
                        'place' => $lang === 'ar' ? ($lesson->place_ar ?? $lesson->place_en) : $lesson->place_en,
                        'price_aed' => $lesson->price_aed,
                        'price_usd' => $lesson->price_usd,
                        'duration' => $lesson->duration,
                        'lesson' => $lesson->lesson ? [
                            'id' => $lesson->lesson->id,
                            'title_en' => $lesson->lesson->title_en,
                            'title_ar' => $lesson->lesson->title_ar,
                            'description_en' => $lesson->lesson->description_en,
                            'description_ar' => $lesson->lesson->description_ar
                        ] : null,
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

            $lesson = PrivateLessonInformation::with('appointment')->find($id);

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
                    'appointment' => $lesson->appointment ? [
                        'id' => $lesson->appointment->id,
                        'date' => $lesson->appointment->date,
                        'start_time' => $lesson->appointment->start_time,
                        'end_time' => $lesson->appointment->end_time
                    ] : null,
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

    // إنشاء درس خصوصي جديد مع موعد
    public function store(Request $request, $private_lesson_id)
    {
        try {
            $validated = $request->validate([
                'place_en' => 'required|string|max:255',
                'place_ar' => 'required|string|max:255',
                'price_aed' => 'required|numeric|min:0',
                'price_usd' => 'required|numeric|min:0',
                'duration' => 'required|integer|min:1',
            ]);

            // التحقق من وجود private lesson
            $privateLessonExists = PrivateLesson::where('id', $private_lesson_id)->exists();
            if (!$privateLessonExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Private lesson not found'
                ], 404);
            }

            // إنشاء private lesson information وربطها بالموعد
            $lesson = PrivateLessonInformation::create([
                'place_en' => $validated['place_en'],
                'place_ar' => $validated['place_ar'],
                'price_aed' => $validated['price_aed'],
                'price_usd' => $validated['price_usd'],
                'duration' => $validated['duration'],
                'private_lesson_id' => $private_lesson_id, // من البارامتر
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Private lesson created successfully',
                'data' => [
                    'lesson' => $lesson
                ]
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
                'message' => 'Failed to create private lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // تحديث درس خصوصي مع الموعد
    public function update(Request $request, $id)
    {
        try {
            $lesson = PrivateLessonInformation::with('appointment')->find($id);

            if (!$lesson) {
                return response()->json([
                    'status' => false,
                    'message' => 'Private lesson not found'
                ], 404);
            }

            if ($lesson->appointment) {
                return response()->json([
                    'status' => false,
                    'message' => 'The private lesson is enrolled'
                ], 404);
            }

            $validated = $request->validate([
                'place_en' => 'required|string|max:255',
                'place_ar' => 'required|string|max:255',
                'price_aed' => 'required|numeric|min:0',
                'price_usd' => 'required|numeric|min:0',
                'duration' => 'required|integer|min:1',
            ]);

            // تحديث بيانات الدرس
            $lesson->update([
                'place_en' => $validated['place_en'],
                'place_ar' => $validated['place_ar'],
                'price_aed' => $validated['price_aed'],
                'price_usd' => $validated['price_usd'],
                'duration' => $validated['duration'],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Private lesson updated successfully',
                'data' => [
                    'lesson' => $lesson
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->getMessage()
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
            $lesson = PrivateLessonInformation::with('appointment')->find($id);

            if (!$lesson) {
                return response()->json([
                    'status' => false,
                    'message' => 'Private lesson not found'
                ], 404);
            }

            if ($lesson->appointment) {
                return response()->json([
                    'status' => false,
                    'message' => 'The private lesson is enrolled'
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
