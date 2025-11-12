<?php

namespace App\Http\Controllers;

use App\HandlesAppointmentTimesTrait;
use App\Models\Consultation;
use App\Models\ConsultationInformation;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Exception;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ConsultationController extends Controller
{
    use HandlesAppointmentTimesTrait;

    // إنشاء consultation جديدة مع appointment وجلسة دفع
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
                'date' => 'required|date_format:d-m-Y',
                'start_time' => 'required|date_format:H:i',
                //'end_time' => 'required|date_format:H:i|after:start_time',
            ]);

            $validated['end_time'] = Carbon::createFromFormat('H:i', $validated['start_time'])->addMinutes($consultationInfo->duration)->format('H:i');

            if (!$this->checkAvailabilityForDay($request->date, $validated['start_time'], $validated['end_time'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'The appointment is outside the availability range.'
                ], 400);
            }

            if (!$this->checkAppointmentConflict($request->date, $validated['start_time'], $validated['end_time'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is another appointment at this time.'
                ], 400);
            }

            // تحويل التاريخ إلى Y-m-d
            $date = Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');

            // إنشاء الموعد أولاً
            $appointment = Appointment::create([
                'date' => $date,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
            ]);

            // إنشاء consultation جديدة وربطها بالموعد
            $consultation = Consultation::create([
                'user_id' => $user->id,
                'information_id' => $information_id,
                'appointment_id' => $appointment->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'payment_status' => 'pending',
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
                    'appointment_id' => $appointment->id,
                ],
            ]);

            $consultation->update(['stripe_session_id' => $session->id]);

            return response()->json([
                'status' => true,
                'message' => 'Consultation created and Stripe checkout session generated successfully',
                'data' => [
                    'consultation' => $consultation,
                    'appointment' => $appointment,
                    'redirect_url' => $session->url,
                    'session_id' => $session->id,
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
                'message' => 'Failed to create consultation and Stripe checkout session',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
