<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Illuminate\Support\Facades\Cache;

class TranslationController extends Controller
{
    public function translate(Request $request)
{
    
    $request->validate([
        'text' => 'required|string|max:5000',
    ]);

    $text = $request->input('text');
    $cacheKey = 'trans_' . md5($text);

    try {
     
        $data = Cache::remember($cacheKey, now()->addDays(30), function () use ($text) {
            $tr = new GoogleTranslate();

            $ar = $tr->setSource('en')->setTarget('ar')->translate($text);
            $fr = $tr->setSource('en')->setTarget('fr')->translate($text);

            
            return [
                'en' => $text,
                'ar' => $ar,
                'fr' => $fr
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
