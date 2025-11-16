<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            [
                'title_en' => 'Forex Trading Fundamentals',
                'title_ar' => 'أساسيات تداول الفوركس',
                'subTitle_en' => 'Master the basics of currency trading',
                'subTitle_ar' => 'إتقان أساسيات تداول العملات',
                'description_en' => 'Learn forex market structure, major currency pairs, technical analysis, and risk management strategies for beginners.',
                'description_ar' => 'تعلم هيكل سوق الفوركس، أزواج العملات الرئيسية، التحليل الفني، واستراتيجيات إدارة المخاطر للمبتدئين.',
                'price_aed' => 299,
                'price_usd' => 81,
                'reviews' => 156,
                'image' => 'courses-seed/image1.jpg',
            ],
            [
                'title_en' => 'Stock Market Investing',
                'title_ar' => 'الاستثمار في سوق الأسهم',
                'subTitle_en' => 'Build wealth through stock investments',
                'subTitle_ar' => 'بناء الثروة من خلال استثمارات الأسهم',
                'description_en' => 'Comprehensive guide to stock market analysis, portfolio building, long-term investment strategies, and dividend investing.',
                'description_ar' => 'دليل شامل لتحليل سوق الأسهم، بناء المحفظة الاستثمارية، استراتيجيات الاستثمار طويلة الأجل، والاستثمار في الأسهم الموزعة للأرباح.',
                'price_aed' => 349,
                'price_usd' => 95,
                'reviews' => 203,
                'image' => 'courses-seed/image2.jpg',
            ],
            [
                'title_en' => 'Cryptocurrency Trading Pro',
                'title_ar' => 'تداول العملات الرقمية للمحترفين',
                'subTitle_en' => 'Advanced crypto trading strategies',
                'subTitle_ar' => 'استراتيجيات متقدمة لتداول العملات الرقمية',
                'description_en' => 'Advanced technical analysis, blockchain fundamentals, altcoin selection, and risk management in volatile crypto markets.',
                'description_ar' => 'تحليل فني متقدم، أساسيات البلوكتشين، اختيار العملات البديلة، وإدارة المخاطر في أسواق العملات الرقمية المتقلبة.',
                'price_aed' => 449,
                'price_usd' => 122,
                'reviews' => 178,
                'image' => 'courses-seed/image1.jpg',
            ],
            [
                'title_en' => 'Technical Analysis Mastery',
                'title_ar' => 'إتقان التحليل الفني',
                'subTitle_en' => 'Read charts like a professional trader',
                'subTitle_ar' => 'اقرأ الرسوم البيانية مثل المتداول المحترف',
                'description_en' => 'Master candlestick patterns, indicators, support/resistance levels, and price action trading strategies.',
                'description_ar' => 'إتقان أنماط الشموع اليابانية، المؤشرات الفنية، مستويات الدعم والمقاومة، واستراتيجيات تداول حركة السعر.',
                'price_aed' => 399,
                'price_usd' => 109,
                'price_usd' => 109,
                'reviews' => 267,
                'image' => 'courses-seed/image2.jpg',
            ],
            [
                'title_en' => 'Options Trading Strategies',
                'title_ar' => 'استراتيجيات تداول الخيارات',
                'subTitle_en' => 'Leverage options for maximum returns',
                'subTitle_ar' => 'استفد من الخيارات لتحقيق أقصى عائد',
                'description_en' => 'Learn call/put options, spreads, straddles, and advanced options strategies for income generation and hedging.',
                'description_ar' => 'تعلم خيارات الشراء والبيع، الاستراتيجيات المركبة، والخيارات المتقدمة لتوليد الدخل والتحوط.',
                'price_aed' => 499,
                'price_usd' => 136,
                'reviews' => 134,
                'image' => 'courses-seed/trading.jpg',
            ],
            [
                'title_en' => 'Risk Management in Trading',
                'title_ar' => 'إدارة المخاطر في التداول',
                'subTitle_en' => 'Protect your capital while maximizing profits',
                'subTitle_ar' => 'احمي رأس مالك مع تعظيم الأرباح',
                'description_en' => 'Essential risk management techniques, position sizing, stop-loss strategies, and psychology of successful traders.',
                'description_ar' => 'تقنيات أساسية لإدارة المخاطر، تحديد حجم المركز، استراتيجيات وقف الخسارة، وسيكولوجية المتداولين الناجحين.',
                'price_aed' => 279,
                'price_usd' => 76,
                'reviews' => 189,
                'image' => 'courses-seed/image2.jpg',
            ],
        ];

        foreach ($courses as $course) {
            // تحقق من وجود الصورة
            $imagePath = public_path($course['image']);
            if (!file_exists($imagePath)) {
                echo "تحذير: الصورة غير موجودة: " . $imagePath . "\n";
                // استخدم صورة افتراضية أو null
                $course['image'] = null;
            }
            
            Course::create($course);
        }

        echo "تم إنشاء " . count($courses) . " درس خاص بنجاح.\n";
    }
}