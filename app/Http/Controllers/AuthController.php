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
use Exception;

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
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'role' => 'in:admin,user'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
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

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid credentials'], 401);
        }

        return $this->respondWithToken($token);
    }

    // عرض بيانات المستخدم الحالي
    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return response()->json(['status' => 'success', 'user' => $user]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Token invalid or expired'], 401);
        }
    }

    // تسجيل الخروج
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['status' => 'success', 'message' => 'Successfully logged out']);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to logout'], 500);
        }
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
            return response()->json(['status'=>'error','message'=>'User not found.'], 404);
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

        return response()->json(['status'=>'success','message'=>'OTP has been sent to your email.'], 200);
    }

    // التحقق من OTP
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status'=>'error','errors'=>$validator->errors()], 422);
        }

        $otpRecord = PasswordOtp::where('otp', $request->otp)->first();
        if (!$otpRecord) return response()->json(['status'=>'error','message'=>'Invalid OTP.'], 400);

        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {
            return response()->json(['status'=>'error','message'=>'OTP expired.'], 400);
        }

        $user = $otpRecord->user;
        $user->otp_verified = true;
        $user->save();

        return response()->json(['status'=>'success','message'=>'OTP verified successfully.']);
    }

    // إعادة تعيين كلمة المرور بعد OTP
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
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

        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            return response()->json(['status'=>'error','message'=>'User not authenticated.'], 401);
        }

        if (!$user->otp_verified) {
            return response()->json(['status'=>'error','message'=>'OTP verification required.'], 403);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'otp_verified' => false
        ]);

        return response()->json(['status'=>'success','message'=>'Password reset successfully.']);
    }

    // تغيير كلمة المرور أثناء تسجيل الدخول
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['status'=>'error','errors'=>$validator->errors()], 422);
        }

        $user = JWTAuth::parseToken()->authenticate();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['status'=>'error','message'=>'Current password is incorrect.'], 401);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['status'=>'success','message'=>'Password changed successfully.']);
    }

    // تحديث الصورة الشخصية
    public function updateProfileImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status'=>'error','errors'=>$validator->errors()], 422);
        }

        $user = JWTAuth::parseToken()->authenticate();

        if ($user->profile_image && file_exists(public_path('storage/' . $user->profile_image))) {
            unlink(public_path('storage/' . $user->profile_image));
        }

        $path = $request->file('profile_image')->store('profile_images', 'public');
        $user->update(['profile_image' => $path]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile image updated successfully.',
            'profile_image_url' => asset('storage/' . $path)
        ]);
    }

    // مساعدة لإرجاع JWT
    protected function respondWithToken($token)
    {
        return response()->json([
            'status' => 'success',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => JWTAuth::parseToken()->authenticate()
        ]);
    }
}
