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
    // Register a new user
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
        ], [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email format is invalid.',
            'email.unique' => 'Email is already taken.',
            'phone_number.required' => 'Phone number is required.',
            'phone_number.unique' => 'Phone number is already taken.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must contain uppercase, lowercase, number, and special character.',
            'password.confirmed' => 'Password confirmation does not match.',
            'profile_image.image' => 'Uploaded file must be an image.',
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

        $token = auth()->guard('api')->login($user);

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // Login user
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Email format is invalid.',
            'password.required' => 'Password is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Email or password is incorrect.'], 401);
        }

        $token = $user->role === 'admin'
            ? auth()->guard('api')->claims(['role' => 'admin'])->setTTL(525600)->login($user)
            : auth()->guard('api')->login($user);

        return $this->respondWithToken($token, $user);
    }

    // Get current authenticated user
    public function me()
    {
        $user = auth()->guard('api')->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Token is invalid or missing.'], 401);
        }

        return response()->json(['status' => 'success', 'user' => $user]);
    }

    // Logout user
    public function logout()
    {
        auth()->guard('api')->logout();
        return response()->json(['status' => 'success', 'message' => 'Logged out successfully.']);
    }

    // Send OTP to user email
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Email format is invalid.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        $otp = rand(100000, 999999);

        PasswordOtp::where('user_id', $user->id)->delete();

        PasswordOtp::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json(['status' => 'success', 'message' => 'OTP has been sent to your email.']);
    }

    // Verify OTP
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
        ], [
            'otp.required' => 'OTP is required.',
            'otp.numeric' => 'OTP must be a number.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $otpRecord = PasswordOtp::where('otp', $request->otp)->first();
        if (!$otpRecord) {
            return response()->json(['status' => 'error', 'message' => 'Invalid OTP.'], 400);
        }

        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'OTP has expired.'], 400);
        }

        $user = $otpRecord->user;
        $user->otp_verified = true;
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'OTP verified successfully.']);
    }

    // Reset password after OTP verification
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
            'new_password' => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]+$/'
            ],
        ], [
            'otp.required' => 'OTP is required.',
            'new_password.required' => 'New password is required.',
            'new_password.confirmed' => 'Password confirmation does not match.',
            'new_password.regex' => 'Password must contain uppercase, lowercase, number, and special character.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $otpRecord = PasswordOtp::where('otp', $request->otp)->first();
        if (!$otpRecord) {
            return response()->json(['status' => 'error', 'message' => 'Invalid OTP.'], 400);
        }

        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'OTP has expired.'], 400);
        }

        $user = $otpRecord->user;
        $user->update(['password' => Hash::make($request->new_password)]);
        $otpRecord->delete();

        return response()->json(['status' => 'success', 'message' => 'Password has been reset successfully.']);
    }

    // Change password while logged in
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/[a-z]/', 'regex:/[A-Z]/',
                'regex:/[0-9]/', 'regex:/[@$!%*#?&]/',
            ],
        ], [
            'current_password.required' => 'Current password is required.',
            'new_password.required' => 'New password is required.',
            'new_password.confirmed' => 'Password confirmation does not match.',
            'new_password.regex' => 'Password must contain uppercase, lowercase, number, and special character.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Token is invalid or missing.'], 401);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Current password is incorrect.'], 401);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['status' => 'success', 'message' => 'Password changed successfully.']);
    }

    // Update profile image
    public function updateProfileImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'profile_image.required' => 'Profile image is required.',
            'profile_image.image' => 'File must be a valid image.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user = auth()->user();

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

    // Helper to return token with user info
    protected function respondWithToken($token, $user)
    {
        $expiresIn = ($user->role === 'admin')
            ? null
            : auth()->guard('api')->factory()->getTTL() * 60;

        return response()->json([
            'status' => 'success',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $expiresIn,
            'user' => $user,
        ]);
    }
}
