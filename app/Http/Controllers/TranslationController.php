<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Illuminate\Support\Facades\Cache;

class TranslationController extends Controller
{
    public function translate(Request $request)
    {
        // تحقق من وجود نص
        $request->validate([
            'text' => 'required|string|max:5000',
        ]);

        $text = $request->input('text');
        // كاش مبني على النص نفسه
        $cacheKey = 'trans_en_' . md5($text);

        try {
            // استخدم الكاش لمدة 30 يوم
            $data = Cache::remember($cacheKey, now()->addDays(30), function () use ($text) {
                $tr = new GoogleTranslate();

                // المصدر عربي، الهدف إنجليزي
                $en = $tr->setSource('ar')->setTarget('en')->translate($text);

                return [
                    'ar' => $text,
                    'en' => $en,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Translation service is currently unavailable. Please try again later.',
            ], 500);
        }
    }
}
