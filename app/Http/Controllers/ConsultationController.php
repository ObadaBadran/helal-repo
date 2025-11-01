<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class ConsultationController extends Controller
{
    const PRICE_USD = 100;
    const PRICE_AED = 350;

    public function createCheckoutSession(Request $request) {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try{
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'required|string|max:20',
                'currency' => 'nullable|string',
                'return_url' => 'required|url',
                'cancel_url' => 'required|url',
            ]);
        } catch(ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors()
            ], 422);
        }

        $currency = $validatedData['currency'] ?? 'usd';
        $amount = $currency === 'usd' ? self::PRICE_USD : self::PRICE_AED;

        $consultation = Consultation::create([
            'user_id' => $user->id,
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'payment_status' => 'pending',
        ]);

        Stripe::setApiKey(env('STRIPE_SECRET'));
        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($currency),
                    'unit_amount' => intval($amount * 100),
                    'product_data' => [
                        'name' => 'Private Consultation',
                        'description' => 'One-on-one consultation session',
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $validatedData['return_url'] . '?order_id=' . $consultation->id,
            'cancel_url' => $validatedData['cancel_url'],
            'metadata' => [
                'consultation_id' => $consultation->id,
                'user_id' => $user->id,
            ],
        ]);

        return response()->json([
            'status' => 'redirect',
            'redirect_url' => $session->url,
        ]);
    }
}
