<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthenticateAPI
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found or not authenticated.'
                ], 401);
            }

            // إرفاق المستخدم مع الـ request لاستخدامه لاحقاً
            $request->merge(['auth_user' => $user]);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token has expired. Please log in again.'
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid token. Please provide a valid token.'
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token not provided. Please include your access token.'
            ], 401);
        }

        return $next($request);
    }
}