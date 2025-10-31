<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;
use Twocheckout;
use Twocheckout_Charge;
use Twocheckout_Error;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

use function PHPUnit\Framework\returnSelf;

class EnrollController extends Controller
{

    public function enrollCourse(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $validatedData = $request->validate([
                'course_id' => 'required|integer|exists:courses,id',
                'currency' => 'nullable|string',
                'return_url' => 'required|url',
                'cancel_url' => 'required|url',
            ]);
        }catch(ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors()
            ], 422);
        }

        $course = Course::findOrFail($validatedData['course_id']);
        $currency = $validatedData['currency'] ?? 'usd';
        $amount = $currency === 'usd' ? $course->price_usd : $course->price_aed;

        if (Enroll::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('payment_status', 'paid')
            ->exists()) {
            return response()->json(['status' => 'info', 'message' => 'You are already enrolled in this course.'], 409);
        }

        $enrollment = Enroll::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'payment_method' => 'Stripe',
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'is_enroll' => false,
        ]);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($currency),
                    'unit_amount' => intval($amount * 100),
                    'product_data' => [
                        'name' => 'Course: ' . $course->title,
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $validatedData['return_url'] . '?order_id=' . $enrollment->id,
            'cancel_url' => $validatedData['cancel_url'],
            'metadata' => [
                'order_id' => $enrollment->id,
                'user_id' => $user->id,
            ],
        ]);

        return response()->json([
            'status' => 'redirect',
            'message' => 'Redirect to Stripe checkout.',
            'redirect_url' => $session->url,
        ]);
    }

    public function showEnrollCourses(Request $request)
    {
        $lang = $request->query('lang', 'en');
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $enrolls = Enroll::with('course')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $enrolls->map(function($enroll) use ($lang) {
            $course = $enroll->course;

            return [
                'enroll_id' => $enroll->id,
                'course_id' => $course->id,
                'title' => $lang === 'ar' ? $course->title_ar : $course->title_en,
                'subTitle' => $lang === 'ar' ? $course->subTitle_ar : $course->subTitle_en,
                'description' => $lang === 'ar' ? $course->description_ar : $course->description_en,
                'price' => $enroll->currency === 'AED' ? $course->price_aed : $course->price_usd,
                'currency' => $enroll->currency,
                'reviews' => $course->reviews,
                'image' => $course->image,
                'payment_status' => $enroll->payment_status,
                'payment_method' => $enroll->payment_method,
                'transaction_id' => $enroll->transaction_id,
                'is_enrolled' => $enroll->is_enroll,
                'enrolled_at' => $enroll->created_at->format('Y-m-d H:i:s')
            ];
        });

        return response()->json([
            'status' => 'success',
            'enroll_courses' => $data
        ]);
    }


}
