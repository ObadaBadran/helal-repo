<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordOtp;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // تسجيل مستخدم جديد
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|unique:users,phone_number',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
                'confirmed',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // تسجيل الدخول
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['status'=>'error', 'message' => 'Invalid credentials'], 401);
        }

        return $this->respondWithToken($token);
    }

    // عرض بيانات المستخدم الحالي
    public function me()
    {
        return response()->json(auth()->user());
    }

    // تسجيل الخروج
    public function logout()
    {
        auth()->logout();
        return response()->json(['status'=>'success', 'message' => 'Successfully logged out']);
    }

    // إرسال OTP للبريد
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['status'=>'error', 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['status'=>'error', 'message' => 'User not found.'], 404);
        }

        $otp = rand(100000, 999999);

        // حذف أي OTP قديمة
        PasswordOtp::where('user_id', $user->id)->delete();

        PasswordOtp::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json([
            'status' => 'success',
            'message' => 'OTP has been sent to your email.'
        ], 200);
    }

    // التحقق من OTP وتغيير كلمة المرور
    public function verifyOtpAndChangePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]+$/'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['status'=>'error','errors'=>$validator->errors()], 422);
        }

        $record = PasswordOtp::where('otp', $request->otp)->first();

        if (!$record) {
            return response()->json(['status'=>'error','message'=>'Invalid OTP.'], 400);
        }

        if (Carbon::now()->greaterThan($record->expires_at)) {
            return response()->json(['status'=>'error','message'=>'OTP has expired.'], 400);
        }

        $user = User::find($record->user_id);
        if (!$user) {
            return response()->json(['status'=>'error','message'=>'User not found for this OTP.'], 404);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        $record->delete();

        return response()->json(['status'=>'success','message'=>'Password changed successfully.'], 200);
    }

    // دالة مساعدة لإرجاع JWT
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }
}
