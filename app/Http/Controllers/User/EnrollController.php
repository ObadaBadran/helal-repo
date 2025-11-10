<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseOnline;
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
            'course_id' => 'nullable|integer|exists:courses,id',
            'course_online_id' => 'nullable|integer|exists:course_online,id',
            'currency' => 'nullable|string',
            'return_url' => 'required|url',
            'cancel_url' => 'required|url',
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'errors' => $e->errors()
        ], 422);
    }

    if (!$request->course_id && !$request->course_online_id) {
        return response()->json(['status' => 'error', 'message' => 'Course type missing'], 422);
    }

    $currency = $validatedData['currency'] ?? 'usd';

    if ($request->course_id) {
        // === كورس عادي ===
        $course = Course::findOrFail($validatedData['course_id']);
        $amount = $currency === 'usd' ? $course->price_usd : $course->price_aed;
        $productName = "Course: " . $course->title_en;
    } else {
        // === كورس أونلاين ===
        $course = CourseOnline::findOrFail($validatedData['course_online_id']);
        $amount = $course->price;
        $productName = "Online Course: " . $course->name;
    }

    // تحقق إن كان مسجل سابقًا
    if (Enroll::where('user_id', $user->id)
        ->when($request->course_id, fn($q)=> $q->where('course_id', $request->course_id))
        ->when($request->course_online_id, fn($q)=> $q->where('course_online_id', $request->course_online_id))
        ->where('payment_status', 'paid')
        ->exists()) {
        return response()->json(['status' => 'info', 'message' => 'Already enrolled'], 409);
    }

    $enrollment = Enroll::create([
        'course_id' => $request->course_id,
        'course_online_id' => $request->course_online_id,
        'user_id' => $user->id,
        'payment_status' => 'pending',
        'payment_method' => 'Stripe',
        'amount' => $amount,
        'currency' => strtoupper($currency),
        'is_enroll' => false,
    ]);

    Stripe::setApiKey(config('services.stripe.secret'));
    
    $session = StripeSession::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => strtolower($currency),
                'unit_amount' => intval($amount * 100),
                'product_data' => [
                    'name' => $productName,
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
            'course_id' => $request->course_id,
            'course_online_id' => $request->course_online_id,
        ],
    ]);

    $enrollment->update(['stripe_session_id' => $session->id]);

    return response()->json([
        'status' => 'redirect',
        'redirect_url' => $session->url,
        'session_id' => $session->id,
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
            ->where('payment_status', 'paid')
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
                'reviews' => $course->reviews,
                'image' => $course->image ? asset($course->image) : null,
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
