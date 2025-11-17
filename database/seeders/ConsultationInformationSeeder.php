<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConsultationInformation;

class ConsultationInformationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $consultationTypes = [
            [
                'type_en' => 'Basic Trading Consultation',
                'type_ar' => 'استشارة تداول أساسية',
                'price_usd' => 50,
                'price_aed' => 185,
                'duration' => 30, // دقائق
            ],
            [
                'type_en' => 'Advanced Market Analysis',
                'type_ar' => 'تحليل سوق متقدم',
                'price_usd' => 100,
                'price_aed' => 370,
                'duration' => 60,
            ],
            [
                'type_en' => 'Portfolio Review Session',
                'type_ar' => 'جلسة مراجعة المحفظة',
                'price_usd' => 75,
                'price_aed' => 275,
                'duration' => 45,
            ],
            [
                'type_en' => 'Risk Management Strategy',
                'type_ar' => 'استراتيجية إدارة المخاطر',
                'price_usd' => 120,
                'price_aed' => 440,
                'duration' => 60,
            ],
            [
                'type_en' => 'Technical Analysis Masterclass',
                'type_ar' => 'دورة متقدمة في التحليل الفني',
                'price_usd' => 150,
                'price_aed' => 550,
                'duration' => 90,
            ],
            [
                'type_en' => 'Forex Trading Guidance',
                'type_ar' => 'إرشادات تداول الفوركس',
                'price_usd' => 80,
                'price_aed' => 295,
                'duration' => 45,
            ],
            [
                'type_en' => 'Cryptocurrency Investment Advice',
                'type_ar' => 'نصيحة استثمار العملات الرقمية',
                'price_usd' => 95,
                'price_aed' => 350,
                'duration' => 50,
            ],
            [
                'type_en' => 'Stock Market Strategy Session',
                'type_ar' => 'جلسة استراتيجية سوق الأسهم',
                'price_usd' => 110,
                'price_aed' => 405,
                'duration' => 55,
            ],
            [
                'type_en' => 'Trading Psychology Coaching',
                'type_ar' => 'تدريب سيكولوجية التداول',
                'price_usd' => 65,
                'price_aed' => 240,
                'duration' => 40,
            ],
            [
                'type_en' => 'Comprehensive Financial Planning',
                'type_ar' => 'تخطيط مالي شامل',
                'price_usd' => 200,
                'price_aed' => 735,
                'duration' => 120,
            ],
        ];

        foreach ($consultationTypes as $type) {
            ConsultationInformation::create($type);
        }
    }
}