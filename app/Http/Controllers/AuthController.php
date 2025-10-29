<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordOtp;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

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
                'required', 'string', 'min:8',
                'regex:/[a-z]/', 'regex:/[A-Z]/',
                'regex:/[0-9]/', 'regex:/[@$!%*#?&]/',
                'confirmed',
            ],
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'role' => 'in:admin,user',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $imagePath = null;
        if ($request->hasFile('profile_image')) {
            $imagePath = $request->file('profile_image')->store('profile_images', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'profile_image' => $imagePath,
            'role' => $request->role ?? 'user',
        ]);

        // إصدار توكن للمستخدم الجديد
        $token = auth()->guard('api')->login($user);

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
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid credentials'], 401);
        }

        if ($user->role === 'admin') {

            $customTTL = 525600;
            $token = auth()->guard('api')->claims(['role' => 'admin'])->setTTL($customTTL)->login($user);
        } else {

            $token = auth()->guard('api')->login($user);
        }

        return $this->respondWithToken($token, $user);
    }

    // المستخدم الحالي — يحتاج أن يكون مستخدم مصادق عليه (token)
    public function me()
    {
        $user = auth()->guard('api')->user();
        return response()->json(['status' => 'success', 'user' => $user]);
    }

    // تسجيل الخروج
    public function logout()
    {
        auth()->guard('api')->logout();
        return response()->json(['status' => 'success', 'message' => 'Successfully logged out']);
    }

    // إرسال OTP (بدون مصادقة)
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), ['email' => 'required|email']);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        $otp = rand(100000, 999999);

        // حذف أي OTP قديمة لنفس المستخدم
        PasswordOtp::where('user_id', $user->id)->delete();

        PasswordOtp::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json(['status' => 'success', 'message' => 'OTP has been sent to your email.'], 200);
    }

    // التحقق من OTP (بدون مصادقة)
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), ['otp' => 'required|numeric']);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $otpRecord = PasswordOtp::where('otp', $request->otp)->first();
        if (!$otpRecord) {
            return response()->json(['status' => 'error', 'message' => 'Invalid OTP.'], 400);
        }

        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'OTP expired.'], 400);
        }

        $user = $otpRecord->user;
        $user->otp_verified = true;
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'OTP verified successfully.']);
    }

    // إعادة تعيين كلمة المرور بعد التحقق من OTP (بدون مصادقة)
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
            'new_password' => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]+$/'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        // البحث عن الـ OTP
        $otpRecord = PasswordOtp::where('otp', $request->otp)->first();

        if (!$otpRecord) {
            return response()->json(['status' => 'error', 'message' => 'Invalid OTP.'], 400);
        }

        // التحقق من انتهاء صلاحية الكود
        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'OTP expired.'], 400);
        }

        // جلب المستخدم المرتبط بالـ OTP
        $user = $otpRecord->user;
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        // تحديث كلمة المرور
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // حذف كود OTP بعد الاستخدام
        $otpRecord->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset successfully.',
        ]);
    }


    // تغيير كلمة المرور أثناء أن المستخدم مسجل دخول
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/[a-z]/', 'regex:/[A-Z]/',
                'regex:/[0-9]/', 'regex:/[@$!%*#?&]/',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = auth()->guard('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication token is missing or invalid.'
            ], 401);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Current password is incorrect.'], 401);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['status' => 'success', 'message' => 'Password changed successfully.']);
    }

    // تحديث الصورة الشخصية
    public function updateProfileImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = auth()->guard('api')->user();

        if ($user->profile_image && file_exists(public_path('storage/' . $user->profile_image))) {
            unlink(public_path('storage/' . $user->profile_image));
        }

        $path = $request->file('profile_image')->store('profile_images', 'public');
        $user->update(['profile_image' => $path]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile image updated successfully.',
            'profile_image_url' => asset('storage/' . $path),
        ]);
    }

    // دالة مساعدة لإرجاع التوكن مع معلومات المستخدم
    protected function respondWithToken($token, $user)
    {
        // تحقق من دور المستخدم لتحديد وقت الانتهاء
        $expiresIn = ($user->role === 'admin')
            ? null // null تعني "صالح دائمًا" تقريبًا
            : auth()->guard('api')->factory()->getTTL() * 60; // بالثواني

        return response()->json([
            'status' => 'success',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $expiresIn,
            'user' => $user,
        ]);
    }
}
