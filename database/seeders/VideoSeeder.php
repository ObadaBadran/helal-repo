<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Video;

class VideoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $videos = [
            [
                'course_id' => 1,
                'path' => 'videos-seed/laravel_intro',
                'youtube_path' => null,
                'title_en' => 'Introduction to Laravel',
                'title_ar' => 'مقدمة في لارافيل',
                'subTitle_en' => 'Getting started with Laravel',
                'subTitle_ar' => 'البدء مع لارافيل',
                'description_en' => 'This video explains the Laravel setup and basic project structure.',
                'description_ar' => 'هذا الفيديو يشرح إعداد لارافيل وهيكل المشروع الأساسي.',
                'cover' => 'covers-seed/laravel_intro.png',
            ],
            [
                'course_id' => 1,
                'path' => 'videos-seed/laravel_routing',
                'youtube_path' => null,
                'title_en' => 'Routing in Laravel',
                'title_ar' => 'الروتنج في لارافيل',
                'subTitle_en' => 'Learn routing and controllers',
                'subTitle_ar' => 'تعلم الروتنج والكونترولرز',
                'description_en' => 'Detailed explanation of Laravel routes and how to use them.',
                'description_ar' => 'شرح مفصل للروتنج في لارافيل وكيفية استخدامها.',
                'cover' => 'covers-seed/laravel_routing.png',
            ],
            [
                'course_id' => 2,
                'path' => 'videos-seed/laravel_intro',
                'youtube_path' => 'https://www.youtube.com/watch?v=fbOUrAFzvpw',
                'title_en' => 'React Components',
                'title_ar' => 'مكونات React',
                'subTitle_en' => 'Creating reusable components',
                'subTitle_ar' => 'إنشاء مكونات قابلة لإعادة الاستخدام',
                'description_en' => 'Learn to create and reuse React components effectively.',
                'description_ar' => 'تعلم كيفية إنشاء وإعادة استخدام مكونات React بفعالية.',
                'cover' => 'covers-seed/react_components.png',
            ],
        ];

        foreach ($videos as $video) {
   $video['path'] = ('/storage/' . $video['path'] . '.mp4'); 
    $video['cover'] = '/storage/' . $video['cover'];
    Video::create($video);
}
    }
}
