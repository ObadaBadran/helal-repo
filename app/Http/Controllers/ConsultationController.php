<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\ConsultationInformation;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Exception;
use Illuminate\Validation\ValidationException;

class ConsultationController extends Controller
{
    // إنشاء consultation جديدة وجلسة دفع
    public function createCheckoutSession(Request $request, $information_id)
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // البحث عن consultation information من الـ route
            $consultationInfo = ConsultationInformation::find($information_id);
            if (!$consultationInfo) {
                return response()->json([
                    'status' => false,
                    'message' => 'Consultation information not found.'
                ], 404);
            }

            $validated = $request->validate([
                'return_url' => 'required|url',
                'cancel_url' => 'required|url',
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'required|string|max:20',
                'consultation_date' => 'required|date',
                'consultation_time' => 'required|date_format:H:i',
            ]);

            // إنشاء consultation جديدة
            $consultation = Consultation::create([
                'user_id' => $user->id,
                'information_id' => $information_id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'payment_status' => 'pending',
                'consultation_date' => $validated['consultation_date'],
                'consultation_time' => $validated['consultation_time'],
                'is_done' => false,
            ]);

            $currency = $consultationInfo->currency ?? 'USD';
            $amount = $consultationInfo->price ?? ($currency === 'USD' ? 100 : 350);

            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($currency),
                        'unit_amount' => intval($amount * 100),
                        'product_data' => [
                            'name' => 'Private Consultation',
                            'description' => $consultationInfo->type_en,
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $validated['return_url'] . '?session_id={CHECKOUT_SESSION_ID}&consultation_id=' . $consultation->id,
                'cancel_url' => $validated['cancel_url'] . '?consultation_id=' . $consultation->id,
                'metadata' => [
                    'consultation_id' => $consultation->id,
                    'user_id' => $user->id,
                    'information_id' => $information_id,
                ],
            ]);

            $consultation->update(['stripe_session_id' => $session->id]);

            return response()->json([
                'status' => true,
                'message' => 'Consultation created and Stripe checkout session generated successfully',
                'data' => [
                    'consultation' => $consultation,
                    'redirect_url' => $session->url,
                    'session_id' => $session->id,
                ]
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
                'message' => 'Failed to create consultation and Stripe checkout session',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}