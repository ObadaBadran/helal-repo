<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // نحاول استخراج المستخدم من التوكن
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found or not authenticated.'
                ], 401);
            }

            // التحقق من أن المستخدم أدمن
            if ($user->role !== 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not authorized to access this resource.'
                ], 403);
            }

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
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }

        return $next($request);
    }
}
