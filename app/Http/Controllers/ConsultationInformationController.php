<?php

namespace App\Http\Controllers;

use App\Models\ConsultationInformation;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class ConsultationInformationController extends Controller
{
    // جلب كل المعلومات
   public function index(Request $request)
{
    try {
        $lang = $request->query('lang', 'en');
        $page = (int)$request->query('page', 1);
        $perPage = (int)$request->query('per_page', 10);

        // استخدام paginate بدلاً من all
        $informationsPaginated = ConsultationInformation::paginate($perPage, ['*'], 'page', $page);

        // تحويل البيانات
        $data = $informationsPaginated->map(function ($info) use ($lang) {
            return [
                'id' => $info->id,
                'type' => $lang === 'ar' ? $info->type_ar : $info->type_en,
                'price_usd' => $info->price_usd,
                'price_aed' => $info->price_aed,
                // 'currency' => $info->currency,
                'duration' => $info->duration,
                'created_at' => $info->created_at,
                'updated_at' => $info->updated_at,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Consultation informations retrieved successfully',
            'data' => $data,
            'pagination' => [
                'current_page' => $informationsPaginated->currentPage(),
                'last_page' => $informationsPaginated->lastPage(),
                'per_page' => $informationsPaginated->perPage(),
                'total' => $informationsPaginated->total(),
            ]
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to retrieve consultation informations',
            'error' => $e->getMessage()
        ], 500);
    }
}

    // عرض معلومات استشارة محددة
    public function show(Request $request, $id)
    {
        try {
            $lang = $request->query('lang', 'en');

            $info = ConsultationInformation::find($id);
            if (!$info) {
                return response()->json([
                    'status' => false,
                    'message' => 'Consultation information not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Consultation information retrieved successfully',
                'data' => [
                    'id' => $info->id,
                    'type' => $lang === 'ar' ? $info->type_ar : $info->type_en,
                    'price_usd' => $info->price_usd,
                    'price_aed' => $info->price_aed,
                    // 'currency' => $info->currency,
                    'duration' => $info->duration,
                    'created_at' => $info->created_at,
                    'updated_at' => $info->updated_at,
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve consultation information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // إنشاء معلومات استشارة جديدة
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'type_en' => 'required|string|max:255',
                'type_ar' => 'required|string|max:255',
                'price_usd' => 'required|numeric|min:0',
                'price_aed' => 'required|numeric|min:0',
                // 'currency' => 'required|in:USD,AED',
                'duration' => 'required|integer|min:1',
            ]);

            // إنشاء information جديدة
            $info = ConsultationInformation::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Consultation information created successfully',
                'data' => $info
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
                'message' => 'Failed to create consultation information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // تحديث معلومات استشارة
    public function update(Request $request, $id)
    {
        try {
            $info = ConsultationInformation::find($id);
            if (!$info) {
                return response()->json([
                    'status' => false,
                    'message' => 'Consultation information not found'
                ], 404);
            }

            $validated = $request->validate([
                'type_en' => 'sometimes|string|max:255',
                'type_ar' => 'sometimes|string|max:255',
                'price_usd' => 'sometimes|numeric|min:0',
                'price_aed' => 'sometimes|numeric|min:0',
                // 'currency' => 'sometimes|in:USD,AED',
                'duration' => 'sometimes|integer|min:1',
            ]);

            $info->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Consultation information updated successfully',
                'data' => $info
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
                'message' => 'Failed to update consultation information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // حذف معلومات استشارة
    public function destroy($id)
    {
        try {
            $info = ConsultationInformation::find($id);
            if (!$info) {
                return response()->json([
                    'status' => false,
                    'message' => 'Consultation information not found'
                ], 404);
            }

            // التحقق إذا كانت هناك استشارات مرتبطة بهذه المعلومات
            $relatedConsultations = Consultation::where('information_id', $id)->get();

            if ($relatedConsultations->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete consultation information because it is associated with existing consultations',
                    'data' => [
                        'related_consultations_count' => $relatedConsultations->count()
                    ]
                ], 422);
            }

            $info->delete();

            return response()->json([
                'status' => true,
                'message' => 'Consultation information deleted successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete consultation information',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
