<?php

namespace App\Http\Controllers;

use App\Jobs\SendConsultationReminderJob;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use App\Models\Enroll;
use App\Models\Consultation;
use App\Models\Course;
use App\Models\CourseOnline;
use Stripe\Stripe;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    /**
     * Stripe Webhook handler
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (UnexpectedValueException $e) {
            Log::error('Invalid payload from Stripe', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid Stripe signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $session = $event->data->object;

        // === Payment completed ===
        if ($event->type === 'checkout.session.completed') {

            if (isset($session->metadata->order_id)) {
                $enrollment = Enroll::find($session->metadata->order_id);

                if ($enrollment && $enrollment->payment_status !== 'paid') {
                    $enrollment->update([
                        'payment_status' => 'paid',
                        'is_enroll' => true,
                        'stripe_session_id' => $session->id,
                    ]);

                    // كورس عادي
                    if (!empty($session->metadata->course_id)) {
                        $course = Course::find($session->metadata->course_id);
                        if ($course) {
                            $course->reviews++;
                            $course->save();
                            Log::info('Regular course enrollment completed.', [
                                'enrollment_id' => $enrollment->id,
                                'course_id' => $course->id
                            ]);
                        }
                    }

                    // كورس أونلاين
                    if (!empty($session->metadata->course_online_id)) {
                        $courseOnline = CourseOnline::find($session->metadata->course_online_id);
                        if ($courseOnline) {
                            Log::info('Online course enrollment completed.', [
                                'enrollment_id' => $enrollment->id,
                                'course_online_id' => $courseOnline->id
                            ]);
                        }
                    }
                }
            }

            // استشارات
            if (isset($session->metadata->consultation_id)) {
                $consultation = Consultation::find($session->metadata->consultation_id);
                if ($consultation && $consultation->payment_status !== 'paid') {
                    $appointment = Appointment::create([
                        'date' => $session->metadata->date,
                        'start_time' => $session->metadata->start_time,
                        'end_time' => $session->metadata->end_time,
                    ]);

                    $consultation->update([
                        'payment_status' => 'paid',
                        'stripe_session_id' => $session->id,
                        'appointment_id' => $appointment->id,
                    ]);

                    $adminEmail = config('services.admin.address');

                    Mail::send('emails.consultation', [
                        'consultation' => $consultation,
                        'locale' => app()->getLocale(),
                    ], function ($message) use ($adminEmail) {
                        $message->to($adminEmail)
                            ->subject(app()->getLocale() === 'ar'
                                ? 'طلب استشارة خاصة جديدة'
                                : 'New Paid Consultation Request');
                    });

                    $sendAt = $consultation->appointment->start_time; // قبل ساعة
                    SendConsultationReminderJob::dispatch($consultation->id)->delay(now()->addMinute());

                    Log::info('Consultation payment completed.' . $sendAt, ['consultation_id' => $consultation->id]);
                }
            }
        }

        // === Session expired ===
        if ($event->type === 'checkout.session.expired') {

            if (isset($session->metadata->order_id)) {
                $enrollment = Enroll::find($session->metadata->order_id);
                if ($enrollment && $enrollment->payment_status !== 'paid') {
                    $enrollment->update([
                        'payment_status' => 'expired',
                        'is_enroll' => false,
                    ]);
                    Log::info('Enrollment session expired.', ['enrollment_id' => $enrollment->id]);
                }
            }

            if (isset($session->metadata->consultation_id)) {
                $consultation = Consultation::find($session->metadata->consultation_id);
                if ($consultation && $consultation->payment_status !== 'paid') {
                    $consultation->update([
                        'payment_status' => 'expired',
                    ]);
                    Log::info('Consultation session expired.', ['consultation_id' => $consultation->id]);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    public function cancel(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing session_id parameter.'
            ], 400);
        }

        $response = [];

        $enrollment = Enroll::where('stripe_session_id', $sessionId)->first();
        $consultation = Consultation::where('stripe_session_id', $sessionId)->first();

        if ($enrollment) {
            if ($enrollment->payment_status !== 'paid') {
                $enrollment->update([
                    'payment_status' => 'canceled',
                    'is_enroll' => false,
                ]);
                Log::info('Enrollment canceled by user.', ['enrollment_id' => $enrollment->id]);
                $response['enrollment'] = [
                    'status' => 'canceled',
                    'enrollment_id' => $enrollment->id
                ];
            } else {
                $response['enrollment'] = [
                    'status' => 'paid',
                    'message' => 'Payment already completed, cannot cancel.',
                    'enrollment_id' => $enrollment->id
                ];
            }
        }

        if ($consultation) {
            if ($consultation->payment_status !== 'paid') {
                $consultation->update([
                    'payment_status' => 'canceled',
                ]);
                Log::info('Consultation payment canceled.', ['consultation_id' => $consultation->id]);
                $response['consultation'] = [
                    'status' => 'canceled',
                    'consultation_id' => $consultation->id
                ];
            } else {
                $response['consultation'] = [
                    'status' => 'paid',
                    'message' => 'Payment already completed, cannot cancel.',
                    'consultation_id' => $consultation->id
                ];
            }
        }

        if (empty($response)) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'No enrollment or consultation found for this session_id.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $response
        ]);
    }
}
