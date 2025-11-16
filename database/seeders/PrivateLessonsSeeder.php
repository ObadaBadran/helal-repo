<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PrivateLesson;

class PrivateLessonsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // أولاً، احذف السجلات القديمة إذا كنت تريد إعادة البداية
        PrivateLesson::query()->delete();

        // مصفوفة من الدروس
        $lessons = [
            [
                'title_en' => 'Introduction to Forex Trading',
                'title_ar' => 'مقدمة في تداول الفوركس',
                'description_en' => 'Learn the basics of Forex trading, including currency pairs, leverage, and risk management.',
                'description_ar' => 'تعلم أساسيات تداول الفوركس، بما في ذلك أزواج العملات والرافعة المالية وإدارة المخاطر.',
                'cover_image' => 'cover-private-seed/coverImage1.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title_en' => 'Technical Analysis Fundamentals',
                'title_ar' => 'أساسيات التحليل الفني',
                'description_en' => 'Understand charts, indicators, and patterns to make informed trading decisions.',
                'description_ar' => 'فهم الرسوم البيانية والمؤشرات والأنماط لاتخاذ قرارات تداول مستنيرة.',
                'cover_image' => 'cover-private-seed/coverImage2.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title_en' => 'Risk Management Strategies',
                'title_ar' => 'استراتيجيات إدارة المخاطر',
                'description_en' => 'Learn how to protect your capital and minimize losses in trading.',
                'description_ar' => 'تعلم كيفية حماية رأس المال وتقليل الخسائر في التداول.',
                'cover_image' => 'cover-private-seed/coverImage3.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title_en' => 'Trading Psychology',
                'title_ar' => 'علم نفس التداول',
                'description_en' => 'Understand emotional control and discipline in trading to improve your results.',
                'description_ar' => 'فهم التحكم العاطفي والانضباط في التداول لتحسين النتائج.',
                'cover_image' => 'cover-private-seed/coverImage4.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($lessons as $lesson) {
            // تحقق من وجود الصورة
            $imagePath = public_path($lesson['cover_image']);
            if (!file_exists($imagePath)) {
                echo "تحذير: الصورة غير موجودة: " . $imagePath . "\n";
                // استخدم صورة افتراضية أو null
                $lesson['cover_image'] = null;
            }
            
            PrivateLesson::create($lesson);
        }

        echo "تم إنشاء " . count($lessons) . " درس خاص بنجاح.\n";
    }
}