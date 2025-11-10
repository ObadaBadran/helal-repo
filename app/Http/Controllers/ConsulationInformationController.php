<?php

namespace App\Http\Controllers;

use App\Models\ConsultationInformation;
use Illuminate\Http\Request;
use Exception;

class ConsulationInformationController extends Controller
{
    
    public function index(Request $request)
    {
        try {
            $lang = $request->query('lang', 'en');

            $consultations = ConsultationInformation::all()->map(function ($consultation) use ($lang) {
                return [
                    'id' => $consultation->id,
                    'type' => $lang === 'ar' ? $consultation->type_ar : $consultation->type_en,
                    'price' => $consultation->price,
                    'currency' => $consultation->currency,
                    'duration' => $consultation->duration,
                    'created_at' => $consultation->created_at,
                    'updated_at' => $consultation->updated_at,
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Consultations retrieved successfully',
                'data' => $consultations
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve consultations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    public function show(Request $request, $id)
    {
        try {
            $lang = $request->query('lang', 'en');

            $consultation = ConsultationInformation::find($id);
            if (!$consultation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Consultation not found'
                ], 404);
            }

            $data = [
                'id' => $consultation->id,
                'type' => $lang === 'ar' ? $consultation->type_ar : $consultation->type_en,
                'price' => $consultation->price,
                'currency' => $consultation->currency,
                'duration' => $consultation->duration,
                'created_at' => $consultation->created_at,
                'updated_at' => $consultation->updated_at,
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Consultation retrieved successfully',
                'data' => $data
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve consultation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   
    public function store(Request $request)
    {
       
        try {
            $validated = $request->validate([
                'type_en' => 'required|string|max:255',
                'type_ar' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'currency' => 'required|in:USD,AED',
                'duration' => 'required|integer|min:1',
            ]);

            $consultation = ConsultationInformation::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Consultation created successfully',
                'data' => $consultation
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create consultation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $consultation = ConsultationInformation::find($id);

            if (!$consultation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Consultation not found'
                ], 404);
            }

            $validated = $request->validate([
                'type_en' => 'sometimes|string|max:255',
                'type_ar' => 'sometimes|string|max:255',
                'price' => 'sometimes|numeric|min:0',
                'currency' => 'sometimes|in:USD,AED',
                'duration' => 'sometimes|integer|min:1',
            ]);

            $consultation->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Consultation updated successfully',
                'data' => $consultation
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update consultation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    public function destroy($id)
    {
        try {
            $consultation = ConsultationInformation::find($id);

            if (!$consultation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Consultation not found'
                ], 404);
            }

            $consultation->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Consultation deleted successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete consultation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
