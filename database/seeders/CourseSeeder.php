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
                'title_en' => 'Laravel for Beginners',
                'title_ar' => 'لارافيل للمبتدئين',
                'subTitle_en' => 'Learn Laravel from scratch',
                'subTitle_ar' => 'تعلم لارافيل من الصفر',
                'description_en' => 'This course teaches you Laravel basics, routing, controllers, and more.',
                'description_ar' => 'هذا الكورس يعلمك أساسيات لارافيل، الروتنج، الكونترولرز والمزيد.',
                'price_aed' => 100,
                'price_usd' => 27,
                'reviews' => 120,
                'image' => 'courses-seed/laravel_beginners.png',
            ],
            [
                'title_en' => 'React JS Essentials',
                'title_ar' => 'أساسيات React JS',
                'subTitle_en' => 'Build dynamic frontend apps',
                'subTitle_ar' => 'بناء تطبيقات واجهة ديناميكية',
                'description_en' => 'Learn React fundamentals and create interactive UIs.',
                'description_ar' => 'تعلم أساسيات React وإنشاء واجهات تفاعلية.',
                'price_aed' => 120,
                'price_usd' => 33,
                'reviews' => 85,
                'image' => 'courses-seed/react_essentials.png',
            ],
        ];

       foreach ($courses as $course) {
            if (!empty($course['image'])) {
                
                $course['image'] = asset($course['image']);
            }

            Course::create($course);
        }
    }
}
