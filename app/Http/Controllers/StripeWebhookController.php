<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Course;
use App\Models\Enroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use Illuminate\Support\Facades\Mail;
use Stripe\Checkout\Session as StripeSession;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
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
                        $course->reviews++;
                        $course->save();
                    }

                    Log::info('Enrollment updated successfully.', ['enrollment_id' => $enrollment->id]);
                }
            }

            if (isset($session->metadata->consultation_id)) {
                $consultation = Consultation::find($session->metadata->consultation_id);
                if ($consultation && $consultation->payment_status !== 'paid') {
                    $consultation->update([
                        'payment_status' => 'paid',
                        'stripe_session_id' => $session->id,
                    ]);

                    // إرسال إيميل لهلال ببيانات الاستشارة
                    Mail::raw(
                        "A new paid consultation request has been received:\n\n".
                        "Name: {$consultation->name}\n".
                        "Email: {$consultation->email}\n".
                        "Phone: {$consultation->phone}\n".
                        "Amount: {$consultation->amount} {$consultation->currency}\n",
                        function ($message) {
                            $message->to('salhanaya8@gmail.com')
                                    ->subject('New Private Consultation Request');
                        }
                    );

                    Log::info('Consultation payment completed.', ['consultation_id' => $consultation->id]);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

}
