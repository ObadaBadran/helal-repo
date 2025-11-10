<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NewsSection;
use App\Models\NewsSectionImage;

class NewsSectionSeeder extends Seeder
{
    public function run(): void
    {
        $sectionsData = [
            [
                'title_en' => 'Technology Innovations',
                'title_ar' => 'الابتكارات التكنولوجية',
                'subtitle_en' => 'Future of AI',
                'subtitle_ar' => 'مستقبل الذكاء الاصطناعي',
                'description_en' => 'Exploring AI advancements and their impact.',
                'description_ar' => 'استكشاف تقدم الذكاء الاصطناعي وتأثيره.',
                'images' => [
                    'seed/news1.jpg',
                    'seed/news2.jpg'
                ]
            ],
            [
                'title_en' => 'Global Economy Trends',
                'title_ar' => 'اتجاهات الاقتصاد العالمي',
                'subtitle_en' => '2025 Outlook',
                'subtitle_ar' => 'توقعات 2025',
                'description_en' => 'Analyzing the future changes in the economy.',
                'description_ar' => 'تحليل التغييرات المستقبلية في الاقتصاد.',
                'images' => [
                    'seed/economy1.jpg',
                    'seed/economy2.jpg'
                ]
            ],
            [
                'title_en' => 'Health & Lifestyle',
                'title_ar' => 'الصحة ونمط الحياة',
                'subtitle_en' => 'Better Habits',
                'subtitle_ar' => 'عادات أفضل',
                'description_en' => 'Tips to improve everyday life and well-being.',
                'description_ar' => 'نصائح لتحسين الحياة اليومية والرفاهية.',
                'images' => [
                    'seed/health1.jpg'
                ]
            ],
        ];

        foreach ($sectionsData as $sec) {
            $section = NewsSection::create([
                'title_en' => $sec['title_en'],
                'title_ar' => $sec['title_ar'],
                'subtitle_en' => $sec['subtitle_en'],
                'subtitle_ar' => $sec['subtitle_ar'],
                'description_en' => $sec['description_en'],
                'description_ar' => $sec['description_ar'],
            ]);

             foreach ($sec['images'] as $img) {
                NewsSectionImage::create([
                    'news_section_id' => $section->id,
                    'image' => asset($img), 
                ]);
            }
        }
    }
}
