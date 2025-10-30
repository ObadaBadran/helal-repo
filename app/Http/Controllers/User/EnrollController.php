<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Twocheckout;
use Twocheckout_Charge;
use Twocheckout_Error;

class EnrollController extends Controller
{
    public function enrollCourse(Request $request)
    {

        try {
            $validatedData = $request->validate([
            'payment_token' => 'required|string',
            'course_id' => 'required| integer|exists:courses,id',
            'addrLine1' => 'required|string|max:255',
            'city' => 'required|string| max:100',
            'zipCode' => 'required|string|max:20',
            'country' => 'required |string',
            'currency' => 'nullable|string'
            ]);
        }catch(ValidationException $e) {
                return response()->json([
                'status' => 'error',
                'errors' => $e->errors()
            ], 422);
        }
        

        $user = auth('api')->user();
        
        $userId = $user->id;
        $courseId = $validatedData['course_id'];
        $currency = $validatedData['currency'] ?? 'USD';
        
        if (Enroll::where('user_id', $userId)
                  ->where('course_id', $courseId)
                  ->where('payment_status', 'paid') 
                  ->exists()) {
            return response()->json(['status' => 'info', 'message' => 'You are already enrolled in this course.'], 409);
        }

        return DB::transaction(function () use ($validatedData, $userId, $courseId, $currency, $request) {
            
            $user = auth('api')->user();
            $course = Course::where('id', $validatedData['course_id'])->first();
            $amount = $currency === 'USD' ? $course->price_usd : $course->price_aed;
            $enrollment = Enroll::create([
                'course_id' => $courseId,
                'user_id' => $userId,
                'payment_status' => 'pending', 
                'payment_method' => 'Card', 
                'amount' => $amount,
                'currency' => $currency, 
                'is_enroll' => false,
            ]);
            
            try {

                Twocheckout::privateKey(env('TCO_PRIVATE_KEY'));
                Twocheckout::sellerId(env('TCO_SELLER_ID'));
                
                if (env('TCO_TEST_MODE') === 'true') {
                    Twocheckout::$baseUrl = 'https://sandbox.2checkout.com'; 
                } 

                $charge = Twocheckout_Charge::auth([
                    "merchantOrderId" => "CRS-" . $enrollment->id,
                    "token" => $validatedData['payment_token'], 
                    "currency" => $currency,
                    "total" => $amount,
                    "billingAddr" => [
                        "name" => $user->name,
                        "addrLine1" => $validatedData['addrLine1'],
                        "city" => $validatedData['city'],
                        "state" => $request->json('state'),
                        "zipCode" => $validatedData['zipCode'],
                        "country" => $validatedData['country'],
                        "email" => $user->email,
                        "phoneNumber" => $user->phone_number,
                    ]
                ]);

                if ($charge['response']['responseCode'] == 'APPROVED') {
                    
                    $enrollment->update([
                        'payment_status' => 'paid',
                        'transaction_id' => $charge['response']['transactionId'],
                        'is_enroll' => true,
                    ]);

                    $course->reviews++;
                    $course->save();

                    return response()->json([
                        'status' => 'success', 
                        'message' => 'Enrollment successful! Payment approved.',
                        'transaction_id' => $enrollment->transaction_id
                    ]);

                } else {
                    $enrollment->update(['payment_status' => 'cancelled']);
                    return response()->json([
                        'status' => 'failure', 
                        'message' => 'Payment failed: ' . $charge['response']['responseMessage']
                    ], 400);
                }

            } catch (Twocheckout_Error $e) {
                $enrollment->update(['payment_status' => 'cancelled']);
                
                throw new \Exception("Payment API Error: " . $e->getMessage()); 
            }
        });
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
