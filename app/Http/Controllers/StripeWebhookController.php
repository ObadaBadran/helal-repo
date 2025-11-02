<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Webhook;
use App\Models\Enroll;
use App\Models\Consultation;
use App\Models\Course;

class StripeWebhookController extends Controller
{
    /**
     * Stripe Webhook handler
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid payload from Stripe', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Invalid Stripe signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            if (isset($session->metadata->order_id)) {
                $enrollment = Enroll::find($session->metadata->order_id);
                if ($enrollment && $enrollment->payment_status !== 'paid') {
                    $enrollment->update([
                        'payment_status' => 'paid',
                        'is_enroll' => true,
                        'stripe_session_id' => $session->id,
                    ]);

                    $course = Course::find($session->metadata->course_id);
                    if ($course) {
                        $course->reviews = $course->reviews + 1;
                        $course->save();
                        
                    }

                    Log::info('Enrollment payment completed.', ['enrollment_id' => $enrollment->id]);
                }
            }

            if (isset($session->metadata->consultation_id)) {
                $consultation = Consultation::find($session->metadata->consultation_id);
                if ($consultation && $consultation->payment_status !== 'paid') {
                    $consultation->update([
                        'payment_status' => 'paid',
                        'stripe_session_id' => $session->id,
                    ]);

                    $adminEmail = env('MAIL_ADMIN_EMAIL');

                    Mail::send('emails.consultation', [
                        'consultation' => $consultation,
                        'locale' => app()->getLocale(),
                    ], function ($message) use ($adminEmail) {
                        $message->to($adminEmail)
                                ->subject(app()->getLocale() === 'ar'
                                    ? 'طلب استشارة خاصة جديدة'
                                    : 'New Paid Consultation Request');
                    });

                    Log::info('Consultation payment completed.', ['consultation_id' => $consultation->id]);
                }
            }
        }

        if ($event->type === 'checkout.session.expired') {
            $session = $event->data->object;

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
