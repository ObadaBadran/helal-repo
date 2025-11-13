<?php

namespace App\Http\Controllers;

use App\HandlesAppointmentTimesTrait;
use App\Models\Consultation;
use App\Models\ConsultationInformation;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
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
                'currency' => 'required|in:USD,AED',
            ]);

            $validated['end_time'] = Carbon::createFromFormat('H:i', $validated['start_time'])->addMinutes($consultationInfo->duration)->format('H:i');

            if (!$this->checkAvailabilityForDay($request->date, $validated['start_time'], $validated['end_time'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to create consultation and Stripe checkout session.',
                    'error' => 'The appointment is outside the availability range.'
                ], 400);
            }

            if (!$this->checkAppointmentConflict($request->date, $validated['start_time'], $validated['end_time'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to create consultation and Stripe checkout session.',
                    'error' => 'There is another appointment at this time.'
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

            $amount = $validated['currency'] === 'USD' ? $consultationInfo->price_usd : $consultationInfo->price_aed;

            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($validated['currency']),
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

             
            $this->sendConsultationConfirmationEmail($consultation, $consultationInfo, $appointment);

           
            $this->sendAdminNotificationEmail($consultation, $consultationInfo, $appointment);


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


    private function sendConsultationConfirmationEmail($consultation, $consultationInfo, $appointment)
{
    try {
        $consultationType = $consultationInfo->type_en;
        $consultationTypeAr = $consultationInfo->type_ar;
        $duration = $consultationInfo->duration;
        $price = $consultation->price;

        $emailContent = 
            "Dear {$consultation->name},\n\n" .
            "Thank you for your payment! Your consultation has been successfully booked.\n\n" .
            "Consultation Details:\n" .
            "Type: {$consultationType}\n" .
            "Date: {$appointment->date}\n" .
            "Time: {$appointment->start_time} - {$appointment->end_time}\n" .
            "Duration: {$duration} minutes\n" .
            "Consultant: {$consultation->name}\n" .
            "Email: {$consultation->email}\n" .
            "Phone: {$consultation->phone}\n\n" .
            "We look forward to assisting you with your consultation.\n\n" .
            "Best regards,\n" .
            "------------------------------\n\n" .
            
            "عزيزي/عزيزتي {$consultation->name},\n\n" .
            "شكراً لك على الدفع! تم حجز استشارتك بنجاح.\n\n" .
            "تفاصيل الاستشارة:\n" .
            "النوع: {$consultationTypeAr}\n" .
            "التاريخ: {$appointment->date}\n" .
            "الوقت: {$appointment->start_time} - {$appointment->end_time}\n" .
            "المدة: {$duration} دقيقة\n" .
            "اسم المستشار: {$consultation->name}\n" .
            "البريد الإلكتروني: {$consultation->email}\n" .
            "رقم الهاتف: {$consultation->phone}\n\n" .
            "نتطلع إلى مساعدتك في استشارتك.\n\n" .
            "مع أطيب التحيات،\n";

        Mail::raw($emailContent, function ($message) use ($consultation, $consultationType) {
            $message->to($consultation->email)
                ->subject("Consultation Confirmation - تأكيد الاستشارة: {$consultationType}");
        });

    } catch (Exception $e) {
        \Log::error('Failed to send consultation confirmation email: ' . $e->getMessage());
    }
}

      private function sendAdminNotificationEmail($consultation, $consultationInfo, $appointment)
{
    try {
        $adminUsers = User::where('role', 'admin')->get();

        if ($adminUsers->isEmpty()) {
            \Log::warning('No admin users found in the database');
            return;
        }

        $consultationType = $consultationInfo->type_en;
        $consultationTypeAr = $consultationInfo->type_ar;
        $duration = $consultationInfo->duration;

        $emailContent = 
            "New Consultation Booking - Immediate Attention Required\n\n" .
            "A new consultation has been booked with the following details:\n\n" .
            "Client Information:\n" .
            "Name: {$consultation->name}\n" .
            "Email: {$consultation->email}\n" .
            "Phone: {$consultation->phone}\n\n" .
            "Consultation Details:\n" .
            "Type: {$consultationType}\n" .
            "Date: {$appointment->date}\n" .
            "Time: {$appointment->start_time} - {$appointment->end_time}\n" .
            "Duration: {$duration} minutes\n" .
            "Booking Time: " . now()->format('Y-m-d H:i:s') . "\n\n" .
            "Please prepare for this consultation session.\n\n" .
            
            "------------------------------\n\n" .
            
            "حجز استشارة جديد - يتطلب اهتماماً فورياً\n\n" .
            "تم حجز استشارة جديدة بالتفاصيل التالية:\n\n" .
            "معلومات العميل:\n" .
            "الاسم: {$consultation->name}\n" .
            "البريد الإلكتروني: {$consultation->email}\n" .
            "رقم الهاتف: {$consultation->phone}\n\n" .
            "تفاصيل الاستشارة:\n" .
            "النوع: {$consultationTypeAr}\n" .
            "التاريخ: {$appointment->date}\n" .
            "الوقت: {$appointment->start_time} - {$appointment->end_time}\n" .
            "المدة: {$duration} دقيقة\n" .
            "وقت الحجز: " . now()->format('Y-m-d H:i:s') . "\n\n" .
            "يرجى التحضير لجلسة الاستشارة هذه.\n\n" 
            ;

        foreach ($adminUsers as $admin) {
            Mail::raw($emailContent, function ($message) use ($admin, $consultation) {
                $message->to($admin->email)
                    ->subject("New Consultation Booking - حجز استشارة جديد: {$consultation->name}");
            });
        }

        \Log::info('Admin notification emails sent to ' . $adminUsers->count() . ' admin users');

    } catch (Exception $e) {
        \Log::error('Failed to send admin notification email: ' . $e->getMessage());
    }
}
}
